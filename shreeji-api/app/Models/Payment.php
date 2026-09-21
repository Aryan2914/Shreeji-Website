<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $fillable = [
        'order_id',
        'razorpay_order_id',
        'razorpay_payment_id',
        'razorpay_signature',
        'method',
        'amount',
        'currency',
        'status',
        'paid_at',
        'refund_id',
        'refunded_at',
        'gateway_response',
    ];

    protected function casts(): array
    {
        return [
            'status' => PaymentStatus::class,
            'amount' => 'decimal:2',
            'paid_at' => 'datetime',
            'refunded_at' => 'datetime',
            'gateway_response' => 'array',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    // ── Helpers ────────────────────────────────────────

    public function isCaptured(): bool
    {
        return $this->status === PaymentStatus::CAPTURED;
    }

    public function isFailed(): bool
    {
        return $this->status === PaymentStatus::FAILED;
    }

    /**
     * Amount in paise for Razorpay (INR × 100).
     */
    public function getAmountInPaiseAttribute(): int
    {
        return (int) round($this->amount * 100);
    }
}
