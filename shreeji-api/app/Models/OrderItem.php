<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id',
        'sku_id',
        'product_name',
        'sku_code',
        'quantity',
        'unit_price',
        'tax_rate',
        'tax_amount',
        'total',
        'hsn_code',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price' => 'decimal:2',
            'tax_rate' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function sku(): BelongsTo
    {
        return $this->belongsTo(ProductSku::class, 'sku_id');
    }

    // ── Helpers ────────────────────────────────────────

    public function getSubtotalAttribute(): float
    {
        return (float) $this->unit_price * $this->quantity;
    }
}
