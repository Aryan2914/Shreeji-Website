<?php

namespace App\Models;

use App\Enums\PriceTier as PriceTierEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PriceTier extends Model
{
    protected $fillable = [
        'sku_id',
        'tier',
        'min_quantity',
        'price',
    ];

    protected function casts(): array
    {
        return [
            'tier' => PriceTierEnum::class,
            'min_quantity' => 'integer',
            'price' => 'decimal:2',
        ];
    }

    public function sku(): BelongsTo
    {
        return $this->belongsTo(ProductSku::class, 'sku_id');
    }
}
