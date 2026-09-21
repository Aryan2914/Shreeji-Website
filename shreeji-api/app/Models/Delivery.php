<?php

namespace App\Models;

use App\Enums\DeliveryProvider;
use App\Enums\DeliveryStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Delivery extends Model
{
    protected $fillable = [
        'order_id',
        'provider',
        'porter_order_id',
        'tracking_url',
        'pickup_address',
        'delivery_address',
        'estimated_pickup_at',
        'picked_up_at',
        'estimated_delivery_at',
        'delivered_at',
        'delivery_charge',
        'status',
        'porter_response',
    ];

    protected function casts(): array
    {
        return [
            'provider' => DeliveryProvider::class,
            'status' => DeliveryStatus::class,
            'estimated_pickup_at' => 'datetime',
            'picked_up_at' => 'datetime',
            'estimated_delivery_at' => 'datetime',
            'delivered_at' => 'datetime',
            'delivery_charge' => 'decimal:2',
            'porter_response' => 'array',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    // ── Helpers ────────────────────────────────────────

    public function isPorter(): bool
    {
        return $this->provider === DeliveryProvider::PORTER;
    }

    public function isDelivered(): bool
    {
        return $this->status === DeliveryStatus::DELIVERED;
    }

    /**
     * Estimated time remaining in minutes.
     */
    public function getEtaMinutesAttribute(): ?int
    {
        if (!$this->estimated_delivery_at || $this->isDelivered()) {
            return null;
        }

        $minutes = now()->diffInMinutes($this->estimated_delivery_at, false);

        return max(0, (int) $minutes);
    }
}
