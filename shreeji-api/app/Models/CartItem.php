<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CartItem extends Model
{
    protected $fillable = [
        'user_id',
        'session_id',
        'sku_id',
        'quantity',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sku(): BelongsTo
    {
        return $this->belongsTo(ProductSku::class, 'sku_id');
    }

    // ── Scopes ─────────────────────────────────────────

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForSession($query, $sessionId)
    {
        return $query->where('session_id', $sessionId);
    }

    // ── Helpers ────────────────────────────────────────

    /**
     * Get line total (price × quantity, excl. tax).
     */
    public function getLineTotalAttribute(): float
    {
        return (float) $this->sku->selling_price * $this->quantity;
    }

    /**
     * Get line tax amount.
     */
    public function getLineTaxAttribute(): float
    {
        $gstRate = $this->sku->product->gst_rate ?? 18;
        return round($this->line_total * ($gstRate / 100), 2);
    }
}
