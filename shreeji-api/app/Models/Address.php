<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Address extends Model
{
    /**
     * Ahmedabad pincodes eligible for express delivery.
     * Covers main Ahmedabad city zones.
     */
    public const EXPRESS_PINCODES = [
        // Ahmedabad city proper
        '380001', '380002', '380003', '380004', '380005', '380006', '380007',
        '380008', '380009', '380010', '380013', '380014', '380015', '380016',
        '380018', '380019', '380021', '380022', '380023', '380024', '380025',
        '380026', '380027', '380028', '380049', '380050', '380051', '380052',
        '380053', '380054', '380055', '380058', '380059', '380060', '380061',
        '380063',
        // SG Highway / Satellite / Prahlad Nagar
        '380015', '380054', '380059',
        // Maninagar (Shreeji's location)
        '380008',
        // Naroda / Odhav / Vatva industrial
        '382330', '382415', '382440', '382445',
        // Gandhinagar (nearby)
        '382010', '382016', '382021', '382024',
    ];

    protected $fillable = [
        'user_id',
        'label',
        'contact_name',
        'contact_phone',
        'address_line_1',
        'address_line_2',
        'landmark',
        'city',
        'state',
        'pincode',
        'latitude',
        'longitude',
        'is_default',
        'is_express_eligible',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
            'is_default' => 'boolean',
            'is_express_eligible' => 'boolean',
        ];
    }

    // ── Relationships ──────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ── Boot ───────────────────────────────────────────

    protected static function booted(): void
    {
        static::saving(function (Address $address) {
            // Auto-compute express eligibility based on pincode
            $address->is_express_eligible = in_array($address->pincode, self::EXPRESS_PINCODES);
        });
    }

    // ── Helpers ────────────────────────────────────────

    public function getFullAddressAttribute(): string
    {
        $parts = array_filter([
            $this->address_line_1,
            $this->address_line_2,
            $this->landmark ? "Near {$this->landmark}" : null,
            $this->city,
            "{$this->state} - {$this->pincode}",
        ]);

        return implode(', ', $parts);
    }

    public function isInAhmedabad(): bool
    {
        return strtolower($this->city) === 'ahmedabad';
    }
}
