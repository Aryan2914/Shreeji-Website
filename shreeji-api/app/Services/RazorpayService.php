<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use App\Enums\PaymentStatus;
use Razorpay\Api\Api as RazorpayApi;

class RazorpayService
{
    protected RazorpayApi $razorpay;

    public function __construct()
    {
        $this->razorpay = new RazorpayApi(
            config('razorpay.key_id'),
            config('razorpay.key_secret')
        );
    }

    /**
     * Create a Razorpay order and store the payment record.
     */
    public function createOrder(Order $order): array
    {
        $amountInPaise = (int) round($order->total_amount * 100);

        $razorpayOrder = $this->razorpay->order->create([
            'amount' => $amountInPaise,
            'currency' => config('razorpay.currency', 'INR'),
            'receipt' => config('razorpay.receipt_prefix', 'SI_') . $order->order_number,
            'notes' => [
                'order_number' => $order->order_number,
                'customer_name' => $order->user->name,
                'customer_phone' => $order->user->phone,
            ],
            'expire_by' => now()->addMinutes(30)->timestamp,
            'payment_capture' => config('razorpay.auto_capture', true) ? 1 : 0,
        ]);

        // Create payment record
        Payment::updateOrCreate(
            ['order_id' => $order->id],
            [
                'razorpay_order_id' => $razorpayOrder->id,
                'amount' => $order->total_amount,
                'currency' => config('razorpay.currency', 'INR'),
                'status' => PaymentStatus::PENDING,
            ]
        );

        // Transition order to payment_pending
        if ($order->status->value === 'new') {
            $order->transitionTo(\App\Enums\OrderStatus::PAYMENT_PENDING, 'Razorpay order created');
        }

        return [
            'razorpay_order_id' => $razorpayOrder->id,
            'razorpay_key_id' => config('razorpay.key_id'),
            'amount' => $amountInPaise,
            'currency' => config('razorpay.currency', 'INR'),
            'name' => config('razorpay.checkout.name', 'Shreeji Infotech'),
            'description' => config('razorpay.checkout.description', ''),
            'image' => config('razorpay.checkout.image', ''),
            'theme_color' => config('razorpay.checkout.theme_color', '#F97316'),
            'prefill' => [
                'name' => $order->user->name,
                'email' => $order->user->email,
                'contact' => $order->user->phone,
            ],
            'order_number' => $order->order_number,
        ];
    }

    /**
     * Verify payment signature (client-side callback).
     *
     * @throws \Exception If signature verification fails
     */
    public function verifyPayment(string $razorpayOrderId, string $razorpayPaymentId, string $razorpaySignature): bool
    {
        $expectedSignature = hash_hmac(
            'sha256',
            $razorpayOrderId . '|' . $razorpayPaymentId,
            config('razorpay.key_secret')
        );

        if (!hash_equals($expectedSignature, $razorpaySignature)) {
            throw new \Exception('Payment signature verification failed');
        }

        return true;
    }

    /**
     * Handle Razorpay webhook event.
     *
     * @throws \Exception If webhook signature is invalid
     */
    public function handleWebhook(string $payload, string $signature): array
    {
        // Verify webhook signature
        $expectedSignature = hash_hmac('sha256', $payload, config('razorpay.webhook_secret'));

        if (!hash_equals($expectedSignature, $signature)) {
            throw new \Exception('Invalid webhook signature');
        }

        $event = json_decode($payload, true);
        $eventType = $event['event'] ?? '';

        return [
            'event_type' => $eventType,
            'payload' => $event['payload'] ?? [],
        ];
    }

    /**
     * Initiate a refund.
     */
    public function refund(string $paymentId, float $amount, array $notes = []): array
    {
        $refund = $this->razorpay->payment->fetch($paymentId)->refund([
            'amount' => (int) round($amount * 100),
            'notes' => $notes,
        ]);

        return [
            'refund_id' => $refund->id,
            'amount' => $refund->amount / 100,
            'status' => $refund->status,
        ];
    }
}
