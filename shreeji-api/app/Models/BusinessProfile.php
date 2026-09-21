<?php

namespace App\Models;

use App\Enums\PriceTier;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BusinessProfile extends Model
{
    protected $fillable = [
        'user_id',
        'company_name',
        'gstin',
        'pan',
        'billing_address_id',
        'credit_limit',
        'credit_used',
        'price_tier',
        'is_verified',
        'verified_at',
    ];

    protected function casts(): array
    {
        return [
            'credit_limit' => 'decimal:2',
            'credit_used' => 'decimal:2',
            'price_tier' => PriceTier::class,
            'is_verified' => 'boolean',
            'verified_at' => 'datetime',
        ];
    }

    // ── Relationships ──────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function billingAddress(): BelongsTo
    {
        return $this->belongsTo(Address::class, 'billing_address_id');
    }

    // ── Helpers ────────────────────────────────────────

    public function availableCredit(): float
    {
        return (float) $this->credit_limit - (float) $this->credit_used;
    }

    /**
     * Validate GSTIN format: 2-digit state code + 10-char PAN + 1 entity + 1 check digit + Z.
     */
    public static function isValidGstin(string $gstin): bool
    {
        return (bool) preg_match('/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/', strtoupper($gstin));
    }
}
