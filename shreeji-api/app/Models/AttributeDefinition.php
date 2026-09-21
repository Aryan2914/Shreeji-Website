<?php

namespace App\Models;

use App\Enums\AttributeType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttributeDefinition extends Model
{
    protected $fillable = [
        'category_id',
        'key',
        'label',
        'type',
        'options',
        'unit',
        'is_required',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'type' => AttributeType::class,
            'options' => 'array',
            'is_required' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    // ── Relationships ──────────────────────────────────

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    // ── Scopes ─────────────────────────────────────────

    public function scopeForCategory($query, $categoryId)
    {
        return $query->where(function ($q) use ($categoryId) {
            $q->where('category_id', $categoryId)
              ->orWhereNull('category_id'); // Global attributes
        });
    }

    public function scopeRequired($query)
    {
        return $query->where('is_required', true);
    }
}
