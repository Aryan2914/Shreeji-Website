<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductSku extends Model
{
    protected $table = 'product_skus';

    protected $fillable = [
        'product_id',
        'sku',
        'barcode',
        'variant_label',
        'cost_price',
        'retail_price',
        'selling_price',
        'stock_quantity',
        'reserved_quantity',
        'min_stock_alert',
        'stock_location',
        'is_active',
        'weight_grams',
        'supplier_id',
    ];

    protected function casts(): array
    {
        return [
            'cost_price' => 'decimal:2',
            'retail_price' => 'decimal:2',
            'selling_price' => 'decimal:2',
            'stock_quantity' => 'integer',
            'reserved_quantity' => 'integer',
            'min_stock_alert' => 'integer',
            'is_active' => 'boolean',
            'weight_grams' => 'integer',
        ];
    }

    // ── Relationships ──────────────────────────────────

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function priceTiers(): HasMany
    {
        return $this->hasMany(PriceTier::class, 'sku_id');
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'sku_id');
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'sku_id');
    }

    public function cartItems(): HasMany
    {
        return $this->hasMany(CartItem::class, 'sku_id');
    }

    // ── Scopes ─────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInStock($query)
    {
        return $query->whereRaw('stock_quantity - reserved_quantity > 0');
    }

    public function scopeLowStock($query)
    {
        return $query->whereRaw('stock_quantity - reserved_quantity <= min_stock_alert')
                     ->whereRaw('stock_quantity - reserved_quantity > 0');
    }

    public function scopeOutOfStock($query)
    {
        return $query->whereRaw('stock_quantity - reserved_quantity <= 0');
    }

    // ── Computed ───────────────────────────────────────

    /**
     * Available stock = total stock - reserved.
     */
    public function getAvailableQuantityAttribute(): int
    {
        return max(0, $this->stock_quantity - $this->reserved_quantity);
    }

    /**
     * Is this SKU running low?
     */
    public function getIsLowStockAttribute(): bool
    {
        return $this->available_quantity > 0 && $this->available_quantity <= $this->min_stock_alert;
    }

    /**
     * Is this SKU completely out?
     */
    public function getIsOutOfStockAttribute(): bool
    {
        return $this->available_quantity <= 0;
    }

    /**
     * Profit margin percentage.
     */
    public function getMarginPercentAttribute(): float
    {
        if ($this->cost_price <= 0) {
            return 0;
        }

        return round((($this->selling_price - $this->cost_price) / $this->cost_price) * 100, 1);
    }

    /**
     * Get the effective price for a given tier and quantity.
     */
    public function getPriceForTier(string $tier, int $quantity = 1): float
    {
        $tierPrice = $this->priceTiers()
            ->where('tier', $tier)
            ->where('min_quantity', '<=', $quantity)
            ->orderByDesc('min_quantity')
            ->first();

        if ($tierPrice) {
            return (float) $tierPrice->price;
        }

        // Fallback to selling price
        return (float) $this->selling_price;
    }
}
