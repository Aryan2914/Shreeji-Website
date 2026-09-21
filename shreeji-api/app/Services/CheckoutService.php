<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\ProductSku;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CheckoutService
{
    public function __construct(
        protected StockService $stockService,
        protected PricingService $pricingService,
        protected InvoiceService $invoiceService,
        protected RazorpayService $razorpayService,
    ) {}

    /**
     * Create an order from the user's cart.
     *
     * @throws \Exception On validation failure or stock issues
     */
    public function createOrder(User $user, array $data): Order
    {
        return DB::transaction(function () use ($user, $data) {

            // 1. Get cart items
            $cartItems = CartItem::where('user_id', $user->id)
                ->with(['sku.product'])
                ->get();

            if ($cartItems->isEmpty()) {
                throw new \Exception('Cart is empty');
            }

            // 2. Validate stock availability for all items
            foreach ($cartItems as $item) {
                if (!$this->stockService->isAvailable($item->sku_id, $item->quantity)) {
                    throw new \Exception(
                        "Insufficient stock for {$item->sku->product->name} (SKU: {$item->sku->sku}). " .
                        "Available: {$item->sku->available_quantity}, Requested: {$item->quantity}"
                    );
                }
            }

            // 3. Calculate pricing with GST
            $pricingItems = $cartItems->map(fn ($item) => [
                'sku' => $item->sku,
                'quantity' => $item->quantity,
            ])->toArray();

            $address = $user->addresses()->findOrFail($data['address_id']);
            $buyerState = $address->state ?? 'Gujarat';
            $totals = $this->pricingService->calculateCartTotal($pricingItems, $user, $buyerState);

            // 4. Calculate shipping
            $shippingAmount = $data['shipping_amount'] ?? 0;

            // 5. Create the order
            $order = Order::create([
                'order_number' => Order::generateOrderNumber(),
                'user_id' => $user->id,
                'address_id' => $data['address_id'],
                'status' => OrderStatus::NEW,
                'order_type' => $user->isBusiness() ? 'business' : 'retail',
                'delivery_type' => $data['delivery_type'] ?? 'standard',
                'subtotal' => $totals['subtotal'],
                'tax_amount' => $totals['tax_total'],
                'shipping_amount' => $shippingAmount,
                'discount_amount' => $data['discount_amount'] ?? 0,
                'total_amount' => $totals['grand_total'] + $shippingAmount - ($data['discount_amount'] ?? 0),
                'po_number' => $data['po_number'] ?? null,
                'customer_note' => $data['customer_note'] ?? null,
                'coupon_code' => $data['coupon_code'] ?? null,
                'estimated_delivery' => $data['estimated_delivery'] ?? null,
            ]);

            // 6. Create order items (with snapshot data)
            foreach ($totals['items'] as $lineItem) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'sku_id' => $lineItem['sku_id'],
                    'product_name' => $lineItem['product_name'],
                    'sku_code' => $lineItem['sku_code'],
                    'quantity' => $lineItem['quantity'],
                    'unit_price' => $lineItem['unit_price'],
                    'tax_rate' => $lineItem['gst_rate'],
                    'tax_amount' => $lineItem['tax']['total_tax'],
                    'total' => $lineItem['line_total'],
                    'hsn_code' => $lineItem['hsn_code'],
                ]);
            }

            // 7. Reserve stock for all items
            foreach ($cartItems as $item) {
                $this->stockService->reserve(
                    $item->sku_id,
                    $item->quantity,
                    $order->id,
                    $user->id
                );
            }

            // 8. Log initial status
            $order->statusHistory()->create([
                'status' => OrderStatus::NEW->value,
                'note' => 'Order created',
                'changed_by' => $user->id,
            ]);

            // 9. Clear the user's cart
            CartItem::where('user_id', $user->id)->delete();

            return $order->load(['items', 'address', 'user']);
        });
    }

    /**
     * Handle successful payment — confirm order and stock.
     */
    /**
     * Handle successful payment — confirm order and stock inside locked transaction.
     * Prevents race conditions between webhook and client-side verify callbacks.
     */
    public function confirmPayment(Order $order, array $paymentData): void
    {
        DB::transaction(function () use ($order, $paymentData) {
            // Lock order and payment rows to ensure thread-safe single execution
            $lockedOrder = Order::where('id', $order->id)->lockForUpdate()->firstOrFail();
            $lockedPayment = Payment::where('order_id', $lockedOrder->id)->lockForUpdate()->first();

            // Strict idempotency: if already paid or captured, exit immediately
            if ($lockedOrder->isPaid() || ($lockedPayment && $lockedPayment->status === PaymentStatus::CAPTURED)) {
                return;
            }

            // Case A: Payment captured for an already-cancelled order (e.g. auto-cancelled after 30m timeout)
            if ($lockedOrder->status === OrderStatus::CANCELLED) {
                // Check if all items can be re-reserved
                $canRevive = true;
                foreach ($lockedOrder->items as $item) {
                    if (!$this->stockService->isAvailable($item->sku_id, $item->quantity)) {
                        $canRevive = false;
                        break;
                    }
                }

                if ($canRevive) {
                    // Re-reserve and immediately confirm sale
                    foreach ($lockedOrder->items as $item) {
                        $this->stockService->reserve($item->sku_id, $item->quantity, $lockedOrder->id, null);
                        $this->stockService->confirmSale($item->sku_id, $item->quantity, $lockedOrder->id, null);
                    }

                    if ($lockedPayment) {
                        $lockedPayment->update([
                            'razorpay_payment_id' => $paymentData['razorpay_payment_id'] ?? null,
                            'razorpay_signature' => $paymentData['razorpay_signature'] ?? null,
                            'method' => $paymentData['method'] ?? null,
                            'status' => PaymentStatus::CAPTURED,
                            'paid_at' => now(),
                            'gateway_response' => $paymentData['gateway_response'] ?? null,
                        ]);
                    }

                    $lockedOrder->transitionTo(OrderStatus::PAID, 'Revived from cancellation: payment captured and stock re-reserved');
                    $this->invoiceService->createInvoiceForOrder($lockedOrder);
                    return;
                } else {
                    // Stock is unavailable: trigger auto-refund via Razorpay
                    if ($paymentData['razorpay_payment_id'] ?? null) {
                        try {
                            $this->razorpayService->refund(
                                $paymentData['razorpay_payment_id'],
                                (float) $lockedOrder->total_amount,
                                ['reason' => 'Stock unavailable after timeout cancellation']
                            );
                        } catch (\Throwable $e) {
                            \Illuminate\Support\Facades\Log::error('Auto-refund failed: ' . $e->getMessage());
                        }
                    }

                    if ($lockedPayment) {
                        $lockedPayment->update([
                            'razorpay_payment_id' => $paymentData['razorpay_payment_id'] ?? null,
                            'status' => PaymentStatus::REFUNDED,
                            'gateway_response' => array_merge($paymentData['gateway_response'] ?? [], [
                                'auto_refund' => 'Order cancelled before payment and stock is out of stock'
                            ]),
                        ]);
                    }

                    $lockedOrder->statusHistory()->create([
                        'status' => OrderStatus::CANCELLED->value,
                        'note' => 'Payment received after cancellation, but stock is out of stock. Auto-refund initiated.',
                    ]);
                    return;
                }
            }

            // Case B: Standard active order payment confirmation
            if ($lockedPayment) {
                $lockedPayment->update([
                    'razorpay_payment_id' => $paymentData['razorpay_payment_id'] ?? null,
                    'razorpay_signature' => $paymentData['razorpay_signature'] ?? null,
                    'method' => $paymentData['method'] ?? null,
                    'status' => PaymentStatus::CAPTURED,
                    'paid_at' => now(),
                    'gateway_response' => $paymentData['gateway_response'] ?? null,
                ]);
            }

            // Transition order to PAID
            $lockedOrder->transitionTo(OrderStatus::PAID, 'Payment confirmed via Razorpay');

            // Confirm stock sale for all items (converts reserved to committed sale)
            foreach ($lockedOrder->items as $item) {
                $this->stockService->confirmSale(
                    $item->sku_id,
                    $item->quantity,
                    $lockedOrder->id
                );
            }

            // Create GST Tax Invoice for the paid order
            $this->invoiceService->createInvoiceForOrder($lockedOrder);
        });
    }

    /**
     * Cancel an order — release reserved stock (if unpaid) or restock & refund (if paid).
     */
    public function cancelOrder(Order $order, string $reason, ?int $cancelledBy = null): void
    {
        DB::transaction(function () use ($order, $reason, $cancelledBy) {
            $lockedOrder = Order::where('id', $order->id)->lockForUpdate()->firstOrFail();
            $lockedPayment = Payment::where('order_id', $lockedOrder->id)->lockForUpdate()->first();

            $isPaid = $lockedOrder->isPaid() || ($lockedPayment && $lockedPayment->status === PaymentStatus::CAPTURED);

            if ($isPaid) {
                // Paid order: Restock actual sold inventory
                foreach ($lockedOrder->items as $item) {
                    $this->stockService->processReturn(
                        $item->sku_id,
                        $item->quantity,
                        $lockedOrder->id,
                        $cancelledBy
                    );
                }

                // Initiate Razorpay refund
                if ($lockedPayment && $lockedPayment->razorpay_payment_id) {
                    try {
                        $refundResult = $this->razorpayService->refund(
                            $lockedPayment->razorpay_payment_id,
                            (float) $lockedOrder->total_amount,
                            ['reason' => $reason]
                        );

                        $lockedPayment->update([
                            'status' => PaymentStatus::REFUNDED,
                            'refund_id' => $refundResult['refund_id'] ?? null,
                            'refunded_at' => now(),
                        ]);
                    } catch (\Throwable $e) {
                        \Illuminate\Support\Facades\Log::error('Razorpay refund failed during order cancellation: ' . $e->getMessage());
                    }
                }
            } else {
                // Unpaid order: Release reserved stock
                foreach ($lockedOrder->items as $item) {
                    $this->stockService->release(
                        $item->sku_id,
                        $item->quantity,
                        $lockedOrder->id,
                        $cancelledBy
                    );
                }
            }

            // Update order status
            $lockedOrder->update([
                'status' => OrderStatus::CANCELLED,
                'cancelled_at' => now(),
                'cancellation_reason' => $reason,
            ]);

            // Log history
            $lockedOrder->statusHistory()->create([
                'status' => OrderStatus::CANCELLED->value,
                'note' => "Cancelled: {$reason}" . ($isPaid ? ' (Stock returned, refund processed)' : ' (Stock released)'),
                'changed_by' => $cancelledBy,
            ]);
        });
    }
}
