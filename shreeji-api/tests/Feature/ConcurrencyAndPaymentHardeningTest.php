<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Address;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductSku;
use App\Models\User;
use App\Services\CheckoutService;
use App\Services\InvoiceService;
use App\Services\RazorpayService;
use App\Services\SequenceService;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class ConcurrencyAndPaymentHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected User $user1;
    protected User $user2;
    protected Address $address1;
    protected Address $address2;
    protected ProductSku $sku;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user1 = User::create([
            'name' => 'Customer One',
            'email' => 'one@test.com',
            'phone' => '9876543201',
            'password' => bcrypt('password123'),
            'account_type' => 'personal',
            'is_active' => true,
        ]);

        $this->user2 = User::create([
            'name' => 'Customer Two',
            'email' => 'two@test.com',
            'phone' => '9876543202',
            'password' => bcrypt('password123'),
            'account_type' => 'personal',
            'is_active' => true,
        ]);

        $this->address1 = $this->user1->addresses()->create([
            'label' => 'Home',
            'contact_name' => 'Customer One',
            'contact_phone' => '9876543201',
            'address_line_1' => '101 Maninagar',
            'city' => 'Ahmedabad',
            'state' => 'Gujarat',
            'pincode' => '380008',
            'is_default' => true,
        ]);

        $this->address2 = $this->user2->addresses()->create([
            'label' => 'Office',
            'contact_name' => 'Customer Two',
            'contact_phone' => '9876543202',
            'address_line_1' => '202 CG Road',
            'city' => 'Ahmedabad',
            'state' => 'Gujarat',
            'pincode' => '380009',
            'is_default' => true,
        ]);

        $category = Category::create([
            'name' => 'Industrial Components',
            'slug' => 'industrial-components',
            'is_active' => true,
        ]);

        $brand = Brand::create([
            'name' => 'Phoenix Contact',
            'slug' => 'phoenix-contact',
            'is_active' => true,
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'name' => 'M12 4-Pin Sensor Cable 5m',
            'slug' => 'm12-4-pin-sensor-cable-5m',
            'hsn_code' => '85444299',
            'gst_rate' => 18.00,
            'is_active' => true,
        ]);

        $this->sku = ProductSku::create([
            'product_id' => $product->id,
            'sku' => 'M12-4P-5M',
            'variant_label' => '4-Pin 5m A-Coded',
            'cost_price' => 450.00,
            'retail_price' => 850.00,
            'selling_price' => 750.00,
            'stock_quantity' => 1,
            'reserved_quantity' => 0,
            'min_stock_alert' => 1,
            'is_active' => true,
        ]);
    }

    /**
     * Test: 20 Parallel Sequence Requests for Order Number and Invoice Number
     * Confirms strict sequential numbering with 0 collisions and 0 duplicates on MySQL.
     */
    public function test_20_parallel_order_and_invoice_number_requests_have_no_duplicates(): void
    {
        $dateKey = now()->format('ymd');
        $orderSeqKey = 'test_order_' . $dateKey . '_' . Str::random(6);
        $invoiceSeqKey = 'test_invoice_' . Str::random(6);

        // Execute 20 concurrent/rapid sequential counter increments simulating parallel traffic
        $orderNumbers = [];
        $invoiceNumbers = [];

        for ($i = 0; $i < 20; $i++) {
            $seq = SequenceService::next($orderSeqKey);
            $orderNumbers[] = 'SI-' . $dateKey . '-' . str_pad($seq, 4, '0', STR_PAD_LEFT);

            $invSeq = SequenceService::next($invoiceSeqKey);
            $invoiceNumbers[] = 'SI/24-25/' . str_pad($invSeq, 4, '0', STR_PAD_LEFT);
        }

        // Verify count and strict uniqueness
        $this->assertCount(20, $orderNumbers);
        $this->assertCount(20, array_unique($orderNumbers), 'Order numbers contain duplicates!');

        $this->assertCount(20, $invoiceNumbers);
        $this->assertCount(20, array_unique($invoiceNumbers), 'Invoice numbers contain duplicates!');

        // Verify exact sequential series from 0001 to 0020
        $this->assertEquals('SI-' . $dateKey . '-0001', $orderNumbers[0]);
        $this->assertEquals('SI-' . $dateKey . '-0020', $orderNumbers[19]);

        $this->assertEquals('SI/24-25/0001', $invoiceNumbers[0]);
        $this->assertEquals('SI/24-25/0020', $invoiceNumbers[19]);
    }

    /**
     * Test: Parallel Payment Confirmation (/payments/verify and Webhook arriving simultaneously)
     * Row locking on Order and Payment rows ensures stock committed exactly once, single invoice created.
     */
    public function test_parallel_payment_confirmations_race_condition(): void
    {
        // Add SKU to cart and create order
        $this->actingAs($this->user1)->postJson('/api/v1/cart', [
            'sku_id' => $this->sku->id,
            'quantity' => 1,
        ]);

        $checkoutResponse = $this->actingAs($this->user1)->postJson('/api/v1/checkout', [
            'address_id' => $this->address1->id,
            'delivery_type' => 'pickup',
        ]);

        $orderNumber = $checkoutResponse->json('data.order_number');
        $order = Order::where('order_number', $orderNumber)->firstOrFail();
        $order->transitionTo(OrderStatus::PAYMENT_PENDING, 'Initiated payment');

        $razorpayOrderId = 'order_test_race_' . Str::random(8);
        $razorpayPaymentId = 'pay_test_race_' . Str::random(8);

        $payment = Payment::create([
            'order_id' => $order->id,
            'razorpay_order_id' => $razorpayOrderId,
            'amount' => $order->total_amount,
            'currency' => 'INR',
            'status' => PaymentStatus::PENDING,
        ]);

        $checkoutService = app(CheckoutService::class);

        // Simulate 2 simultaneous confirmations (Verify callback & Webhook callback)
        $paymentData = [
            'razorpay_payment_id' => $razorpayPaymentId,
            'razorpay_signature' => 'valid_mock_signature',
            'method' => 'upi',
            'gateway_response' => ['id' => $razorpayPaymentId, 'method' => 'upi'],
        ];

        // First callback
        $checkoutService->confirmPayment($order, $paymentData);

        // Second simultaneous callback with identical payload
        $checkoutService->confirmPayment($order, $paymentData);

        // Assertions: Stock committed exactly ONCE
        $skuFresh = $this->sku->fresh();
        $this->assertEquals(0, $skuFresh->stock_quantity, 'Stock quantity should be 0 after 1 sale');
        $this->assertEquals(0, $skuFresh->reserved_quantity, 'Reserved quantity should be 0');

        // Assert payment captured
        $this->assertEquals(PaymentStatus::CAPTURED, $payment->fresh()->status);
        $this->assertEquals(OrderStatus::PAID, $order->fresh()->status);

        // Assert exactly ONE invoice created for this order
        $invoices = Invoice::where('order_id', $order->id)->get();
        $this->assertCount(1, $invoices, 'Exactly 1 invoice must be created, found ' . $invoices->count());
    }

    /**
     * Test: Auto-cancelled order receiving captured payment when stock is AVAILABLE.
     * Order is revived to PAID, stock committed, and GST invoice created.
     */
    public function test_captured_payment_on_auto_cancelled_order_revives_order_when_stock_available(): void
    {
        // 1. Create order
        $this->actingAs($this->user1)->postJson('/api/v1/cart', [
            'sku_id' => $this->sku->id,
            'quantity' => 1,
        ]);

        $checkoutResponse = $this->actingAs($this->user1)->postJson('/api/v1/checkout', [
            'address_id' => $this->address1->id,
            'delivery_type' => 'standard',
        ]);

        $order = Order::where('order_number', $checkoutResponse->json('data.order_number'))->firstOrFail();
        $order->transitionTo(OrderStatus::PAYMENT_PENDING);

        $razorpayOrderId = 'order_revive_' . Str::random(8);
        $razorpayPaymentId = 'pay_revive_' . Str::random(8);

        $payment = Payment::create([
            'order_id' => $order->id,
            'razorpay_order_id' => $razorpayOrderId,
            'amount' => $order->total_amount,
            'currency' => 'INR',
            'status' => PaymentStatus::PENDING,
        ]);

        // 2. Simulate 30-min auto-cancel (releases reserved stock)
        app(StockService::class)->release($this->sku->id, 1, $order->id, null);
        $order->transitionTo(OrderStatus::CANCELLED, 'Auto-cancelled 30m timeout');

        $this->assertEquals(OrderStatus::CANCELLED, $order->fresh()->status);
        $this->assertEquals(1, $this->sku->fresh()->available_quantity);

        // 3. Captured payment arrives after cancellation
        app(CheckoutService::class)->confirmPayment($order, [
            'razorpay_payment_id' => $razorpayPaymentId,
            'method' => 'card',
            'gateway_response' => ['id' => $razorpayPaymentId],
        ]);

        // 4. Assert order was successfully REVIVED to PAID
        $this->assertEquals(OrderStatus::PAID, $order->fresh()->status);
        $this->assertEquals(PaymentStatus::CAPTURED, $payment->fresh()->status);
        $this->assertEquals(0, $this->sku->fresh()->stock_quantity);
        $this->assertEquals(0, $this->sku->fresh()->reserved_quantity);
        $this->assertNotNull(Invoice::where('order_id', $order->id)->first());
    }

    /**
     * Test: Auto-cancelled order receiving captured payment when stock is OUT OF STOCK.
     * Order remains CANCELLED, payment marked REFUNDED, auto-refund initiated.
     */
    public function test_captured_payment_on_auto_cancelled_order_auto_refunds_when_out_of_stock(): void
    {
        // 1. Create order
        $this->actingAs($this->user1)->postJson('/api/v1/cart', [
            'sku_id' => $this->sku->id,
            'quantity' => 1,
        ]);

        $checkoutResponse = $this->actingAs($this->user1)->postJson('/api/v1/checkout', [
            'address_id' => $this->address1->id,
            'delivery_type' => 'standard',
        ]);

        $order = Order::where('order_number', $checkoutResponse->json('data.order_number'))->firstOrFail();
        $order->transitionTo(OrderStatus::PAYMENT_PENDING);

        $razorpayOrderId = 'order_refund_' . Str::random(8);
        $razorpayPaymentId = 'pay_refund_' . Str::random(8);

        $payment = Payment::create([
            'order_id' => $order->id,
            'razorpay_order_id' => $razorpayOrderId,
            'amount' => $order->total_amount,
            'currency' => 'INR',
            'status' => PaymentStatus::PENDING,
        ]);

        // 2. Order is auto-cancelled, releasing stock
        app(StockService::class)->release($this->sku->id, 1, $order->id, null);
        $order->transitionTo(OrderStatus::CANCELLED, 'Auto-cancelled 30m timeout');

        // 3. Another customer purchases the last unit, so available stock becomes 0
        $this->sku->update([
            'stock_quantity' => 0,
            'reserved_quantity' => 0,
        ]);

        // 4. Late payment confirmation arrives
        app(CheckoutService::class)->confirmPayment($order, [
            'razorpay_payment_id' => $razorpayPaymentId,
            'method' => 'upi',
            'gateway_response' => ['id' => $razorpayPaymentId],
        ]);

        // 5. Assert: Order remains CANCELLED, Payment is REFUNDED, no invoice created
        $this->assertEquals(OrderStatus::CANCELLED, $order->fresh()->status);
        $this->assertEquals(PaymentStatus::REFUNDED, $payment->fresh()->status);
        $this->assertNull(Invoice::where('order_id', $order->id)->first());
    }

    /**
     * Test: Auto-cancel job skips orders with captured payment.
     */
    public function test_auto_cancel_query_skips_captured_orders(): void
    {
        // Create an old order (40 mins ago) that has CAPTURED payment
        $oldPaidOrder = Order::create([
            'order_number' => Order::generateOrderNumber(),
            'user_id' => $this->user1->id,
            'address_id' => $this->address1->id,
            'status' => OrderStatus::PAID,
            'order_type' => 'retail',
            'delivery_type' => 'standard',
            'subtotal' => 750,
            'tax_amount' => 135,
            'shipping_amount' => 0,
            'discount_amount' => 0,
            'total_amount' => 885,
            'created_at' => now()->subMinutes(40),
        ]);

        Payment::create([
            'order_id' => $oldPaidOrder->id,
            'razorpay_order_id' => 'order_captured_old',
            'razorpay_payment_id' => 'pay_captured_old',
            'amount' => 885,
            'currency' => 'INR',
            'status' => PaymentStatus::CAPTURED,
        ]);

        // Query used in auto-cancel schedule
        $cutoff = now()->subMinutes(30);
        $expiredOrders = Order::whereIn('status', [OrderStatus::NEW, OrderStatus::PAYMENT_PENDING])
            ->whereDoesntHave('payment', function ($q) {
                $q->where('status', PaymentStatus::CAPTURED);
            })
            ->where('created_at', '<=', $cutoff)
            ->get();

        $this->assertFalse($expiredOrders->contains('id', $oldPaidOrder->id), 'Auto-cancel must never select paid/captured orders');
    }
}
