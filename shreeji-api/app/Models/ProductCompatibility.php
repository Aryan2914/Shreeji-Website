<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductCompatibility extends Model
{
    protected $fillable = [
        'product_id',
        'compatible_product_id',
        'compatibility_type',
        'note',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function compatibleProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'compatible_product_id');
    }
}
