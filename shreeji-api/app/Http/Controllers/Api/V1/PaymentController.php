<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Services\CheckoutService;
use App\Services\RazorpayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    public function __construct(
        protected RazorpayService $razorpayService,
        protected CheckoutService $checkoutService,
    ) {}

    /**
     * Create a Razorpay order for an existing order.
     * POST /api/v1/payments/create-order
     */
    public function createOrder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'order_number' => ['required', 'string', 'exists:orders,order_number'],
        ]);

        $order = Order::where('order_number', $validated['order_number'])
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        // Ensure order is in a payable state
        if (!in_array($order->status->value, ['new', 'payment_pending'])) {
            return response()->json([
                'message' => 'This order cannot be paid. Current status: ' . $order->status->label(),
            ], 422);
        }

        try {
            $paymentData = $this->razorpayService->createOrder($order);

            if ($order->status === \App\Enums\OrderStatus::NEW) {
                $order->transitionTo(\App\Enums\OrderStatus::PAYMENT_PENDING, 'Razorpay order initiated');
            }

            return response()->json([
                'message' => 'Payment order created',
                'data' => $paymentData,
            ]);
        } catch (\Exception $e) {
            Log::error('Razorpay order creation failed', [
                'order' => $order->order_number,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Failed to create payment order. Please try again.',
            ], 500);
        }
    }

    /**
     * Verify payment after Razorpay checkout (client-side callback).
     * POST /api/v1/payments/verify
     */
    public function verify(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'razorpay_order_id' => ['required', 'string'],
            'razorpay_payment_id' => ['required', 'string'],
            'razorpay_signature' => ['required', 'string'],
        ]);

        try {
            // Verify signature
            $this->razorpayService->verifyPayment(
                $validated['razorpay_order_id'],
                $validated['razorpay_payment_id'],
                $validated['razorpay_signature']
            );

            // Find the payment and order
            $payment = Payment::where('razorpay_order_id', $validated['razorpay_order_id'])->firstOrFail();
            $order = $payment->order;

            // Security check: ensure order belongs to authenticated user
            if ($order->user_id !== $request->user()->id) {
                return response()->json([
                    'message' => 'Unauthorized: Cannot verify payment for another user\'s order.'
                ], 403);
            }

            // Confirm the payment
            $this->checkoutService->confirmPayment($order, [
                'razorpay_payment_id' => $validated['razorpay_payment_id'],
                'razorpay_signature' => $validated['razorpay_signature'],
                'gateway_response' => $validated,
            ]);

            return response()->json([
                'message' => 'Payment verified successfully',
                'data' => [
                    'order_number' => $order->order_number,
                    'status' => $order->fresh()->status,
                    'total_amount' => $order->total_amount,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Payment verification failed', [
                'razorpay_order_id' => $validated['razorpay_order_id'],
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Payment verification failed: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Handle Razorpay webhook.
     * POST /api/v1/payments/webhook
     */
    public function webhook(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $signature = $request->header('X-Razorpay-Signature', '');

        try {
            $event = $this->razorpayService->handleWebhook($payload, $signature);

            match ($event['event_type']) {
                'payment.captured' => $this->handlePaymentCaptured($event['payload']),
                'payment.failed' => $this->handlePaymentFailed($event['payload']),
                'refund.processed' => $this->handleRefundProcessed($event['payload']),
                default => Log::info('Unhandled Razorpay webhook event', ['type' => $event['event_type']]),
            };

            return response()->json(['status' => 'ok']);
        } catch (\Exception $e) {
            Log::error('Razorpay webhook error', ['error' => $e->getMessage()]);
            return response()->json(['status' => 'error'], 400);
        }
    }

    private function handlePaymentCaptured(array $payload): void
    {
        $paymentEntity = $payload['payment']['entity'] ?? [];
        $razorpayOrderId = $paymentEntity['order_id'] ?? null;

        if (!$razorpayOrderId) return;

        $payment = Payment::where('razorpay_order_id', $razorpayOrderId)->first();
        if (!$payment) return;

        // Idempotency: skip if already captured or order paid
        if ($payment->status === \App\Enums\PaymentStatus::CAPTURED || $payment->order->isPaid()) {
            return;
        }

        $order = $payment->order;

        // Amount verification: paid amount (in paise) must match order total
        $paidPaise = (int) ($paymentEntity['amount'] ?? 0);
        $expectedPaise = (int) round($order->total_amount * 100);

        if ($paidPaise < $expectedPaise) {
            Log::error('Payment amount mismatch in webhook', [
                'order' => $order->order_number,
                'paid_paise' => $paidPaise,
                'expected_paise' => $expectedPaise,
            ]);
            $payment->update([
                'status' => \App\Enums\PaymentStatus::FAILED,
                'gateway_response' => array_merge($paymentEntity, ['error' => 'Amount mismatch']),
            ]);
            return;
        }

        $this->checkoutService->confirmPayment($order, [
            'razorpay_payment_id' => $paymentEntity['id'] ?? null,
            'method' => $paymentEntity['method'] ?? null,
            'gateway_response' => $paymentEntity,
        ]);
    }

    private function handlePaymentFailed(array $payload): void
    {
        $paymentEntity = $payload['payment']['entity'] ?? [];
        $razorpayOrderId = $paymentEntity['order_id'] ?? null;

        if (!$razorpayOrderId) return;

        $payment = Payment::where('razorpay_order_id', $razorpayOrderId)->first();
        if (!$payment) return;

        $payment->update([
            'status' => \App\Enums\PaymentStatus::FAILED,
            'gateway_response' => $paymentEntity,
        ]);

        Log::warning('Payment failed', [
            'order' => $payment->order->order_number,
            'razorpay_order_id' => $razorpayOrderId,
        ]);
    }

    private function handleRefundProcessed(array $payload): void
    {
        $refundEntity = $payload['refund']['entity'] ?? [];
        $paymentId = $refundEntity['payment_id'] ?? null;

        if (!$paymentId) return;

        $payment = Payment::where('razorpay_payment_id', $paymentId)->first();
        if (!$payment) return;

        $payment->update([
            'status' => \App\Enums\PaymentStatus::REFUNDED,
            'refund_id' => $refundEntity['id'] ?? null,
            'refunded_at' => now(),
        ]);
    }
}
