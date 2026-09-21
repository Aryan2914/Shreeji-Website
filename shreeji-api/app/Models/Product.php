<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Product extends Model
{
    use HasSlug, SoftDeletes, Searchable;

    protected $fillable = [
        'category_id',
        'brand_id',
        'name',
        'slug',
        'short_description',
        'description',
        'hsn_code',
        'gst_rate',
        'warranty_months',
        'warranty_description',
        'is_active',
        'is_featured',
        'is_express_eligible',
        'weight_grams',
        'meta_title',
        'meta_description',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'gst_rate' => 'decimal:2',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'is_express_eligible' => 'boolean',
            'weight_grams' => 'integer',
            'sort_order' => 'integer',
            'warranty_months' => 'integer',
        ];
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('name')
            ->saveSlugsTo('slug');
    }

    // ── Relationships ──────────────────────────────────

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function skus(): HasMany
    {
        return $this->hasMany(ProductSku::class);
    }

    public function activeSku()
    {
        return $this->skus()->where('is_active', true);
    }

    /**
     * Get the primary (cheapest active) SKU.
     */
    public function primarySku()
    {
        return $this->skus()
            ->where('is_active', true)
            ->orderBy('selling_price')
            ->first();
    }

    public function attributes(): HasMany
    {
        return $this->hasMany(ProductAttribute::class)->orderBy('sort_order');
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    public function primaryImage()
    {
        return $this->images()->orderBy('sort_order')->first();
    }

    public function compatibilities(): HasMany
    {
        return $this->hasMany(ProductCompatibility::class);
    }

    public function compatibleProducts()
    {
        return $this->belongsToMany(
            Product::class,
            'product_compatibilities',
            'product_id',
            'compatible_product_id'
        )->withPivot('compatibility_type', 'note');
    }

    public function wishlists(): HasMany
    {
        return $this->hasMany(Wishlist::class);
    }

    // ── Scopes ─────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeExpressEligible($query)
    {
        return $query->where('is_express_eligible', true);
    }

    public function scopeInStock($query)
    {
        return $query->whereHas('skus', function ($q) {
            $q->where('is_active', true)
              ->whereRaw('stock_quantity - reserved_quantity > 0');
        });
    }

    public function scopeInCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    public function scopeByBrand($query, $brandId)
    {
        return $query->where('brand_id', $brandId);
    }

    // ── Meilisearch ────────────────────────────────────

    /**
     * Get the indexable data array for Meilisearch.
     */
    public function toSearchableArray(): array
    {
        $attributes = $this->attributes()
            ->where('is_searchable', true)
            ->get();

        $skus = $this->skus()->where('is_active', true)->get();

        $data = [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'short_description' => $this->short_description,
            'category' => $this->category?->name,
            'category_slug' => $this->category?->slug,
            'category_id' => $this->category_id,
            'brand' => $this->brand?->name,
            'brand_slug' => $this->brand?->slug,
            'brand_id' => $this->brand_id,
            'hsn_code' => $this->hsn_code,
            'is_featured' => $this->is_featured,
            'is_express_eligible' => $this->is_express_eligible,
            'in_stock' => $skus->contains(fn ($sku) => ($sku->stock_quantity - $sku->reserved_quantity) > 0),
            'min_price' => $skus->min('selling_price'),
            'max_price' => $skus->max('selling_price'),
            'sku_codes' => $skus->pluck('sku')->toArray(),
            'image' => $this->primaryImage()?->url,
            'created_at_ts' => $this->created_at?->timestamp,
        ];

        // Flatten EAV attributes into searchable fields
        foreach ($attributes as $attr) {
            $data["attr_{$attr->attribute_key}"] = $attr->attribute_value;

            // Also add a combined search string for the attribute
            if ($attr->attribute_unit) {
                $data["attr_{$attr->attribute_key}_full"] = "{$attr->attribute_value} {$attr->attribute_unit}";
            }
        }

        // Combined search text for better matching ("m12 4 pin 5m", "16gb ddr4")
        $searchParts = [$this->name, $this->brand?->name, $this->category?->name];
        foreach ($attributes as $attr) {
            $searchParts[] = $attr->attribute_value;
            if ($attr->attribute_unit) {
                $searchParts[] = "{$attr->attribute_value}{$attr->attribute_unit}";
            }
        }
        foreach ($skus as $sku) {
            $searchParts[] = $sku->sku;
        }
        $data['search_text'] = implode(' ', array_filter($searchParts));

        return $data;
    }

    /**
     * Determine if the model should be searchable.
     */
    public function shouldBeSearchable(): bool
    {
        return $this->is_active && !$this->trashed();
    }

    // ── Helpers ────────────────────────────────────────

    /**
     * Get the starting price (cheapest active SKU selling price).
     */
    public function getStartingPriceAttribute(): ?float
    {
        return $this->skus()
            ->where('is_active', true)
            ->min('selling_price');
    }

    /**
     * Check if any SKU has stock available.
     */
    public function getIsInStockAttribute(): bool
    {
        return $this->skus()
            ->where('is_active', true)
            ->whereRaw('stock_quantity - reserved_quantity > 0')
            ->exists();
    }

    /**
     * Get total available stock across all SKUs.
     */
    public function getTotalStockAttribute(): int
    {
        return $this->skus()
            ->where('is_active', true)
            ->selectRaw('SUM(stock_quantity - reserved_quantity) as available')
            ->value('available') ?? 0;
    }

    /**
     * Get technical specs as key-value array.
     */
    public function getSpecsAttribute(): array
    {
        return $this->attributes()
            ->get()
            ->mapWithKeys(function ($attr) {
                $value = $attr->attribute_unit
                    ? "{$attr->attribute_value} {$attr->attribute_unit}"
                    : $attr->attribute_value;

                return [$attr->attribute_key => $value];
            })
            ->toArray();
    }
}
