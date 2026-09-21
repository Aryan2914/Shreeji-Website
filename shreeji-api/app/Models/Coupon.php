<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    protected $fillable = [
        'code',
        'type',
        'value',
        'min_order_amount',
        'max_discount',
        'usage_limit',
        'used_count',
        'starts_at',
        'expires_at',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'decimal:2',
            'min_order_amount' => 'decimal:2',
            'max_discount' => 'decimal:2',
            'usage_limit' => 'integer',
            'used_count' => 'integer',
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    // ── Scopes ─────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeValid($query)
    {
        return $query->active()
            ->where('starts_at', '<=', now())
            ->where('expires_at', '>=', now())
            ->where(function ($q) {
                $q->whereNull('usage_limit')
                  ->orWhereRaw('used_count < usage_limit');
            });
    }

    // ── Helpers ────────────────────────────────────────

    /**
     * Check if this coupon is currently valid.
     */
    public function isValid(): bool
    {
        return $this->is_active
            && now()->between($this->starts_at, $this->expires_at)
            && ($this->usage_limit === null || $this->used_count < $this->usage_limit);
    }

    /**
     * Calculate discount amount for a given order subtotal.
     */
    public function calculateDiscount(float $subtotal): float
    {
        if (!$this->isValid()) {
            return 0;
        }

        if ($this->min_order_amount && $subtotal < $this->min_order_amount) {
            return 0;
        }

        if ($this->type === 'percentage') {
            $discount = $subtotal * ($this->value / 100);

            if ($this->max_discount) {
                $discount = min($discount, $this->max_discount);
            }

            return round($discount, 2);
        }

        // Fixed discount
        return min($this->value, $subtotal);
    }

    /**
     * Increment usage count.
     */
    public function markUsed(): void
    {
        $this->increment('used_count');
    }
}
