<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductAttribute extends Model
{
    protected $fillable = [
        'product_id',
        'attribute_key',
        'attribute_value',
        'attribute_unit',
        'is_filterable',
        'is_searchable',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_filterable' => 'boolean',
            'is_searchable' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    // ── Relationships ──────────────────────────────────

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    // ── Helpers ────────────────────────────────────────

    /**
     * Get formatted display value with unit.
     */
    public function getDisplayValueAttribute(): string
    {
        if ($this->attribute_unit) {
            return "{$this->attribute_value} {$this->attribute_unit}";
        }

        return $this->attribute_value;
    }
}
