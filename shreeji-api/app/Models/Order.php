<?php

namespace App\Models;

use App\Enums\DeliveryType;
use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    protected $fillable = [
        'order_number',
        'user_id',
        'address_id',
        'status',
        'order_type',
        'delivery_type',
        'subtotal',
        'tax_amount',
        'shipping_amount',
        'discount_amount',
        'total_amount',
        'po_number',
        'customer_note',
        'admin_note',
        'coupon_code',
        'estimated_delivery',
        'delivered_at',
        'cancelled_at',
        'cancellation_reason',
    ];

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'delivery_type' => DeliveryType::class,
            'subtotal' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'shipping_amount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'estimated_delivery' => 'datetime',
            'delivered_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    // ── Relationships ──────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function address(): BelongsTo
    {
        return $this->belongsTo(Address::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class)->orderByDesc('created_at');
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }

    public function delivery(): HasOne
    {
        return $this->hasOne(Delivery::class);
    }

    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class);
    }

    // ── Scopes ─────────────────────────────────────────

    public function scopeByStatus($query, OrderStatus $status)
    {
        return $query->where('status', $status);
    }

    public function scopeExpress($query)
    {
        return $query->where('delivery_type', DeliveryType::EXPRESS);
    }

    public function scopeRecent($query)
    {
        return $query->orderByDesc('created_at');
    }

    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    // ── State Machine ──────────────────────────────────

    /**
     * Transition order to a new status with validation.
     */
    public function transitionTo(OrderStatus $newStatus, ?string $note = null, ?int $changedBy = null): bool
    {
        if (!$this->status->canTransitionTo($newStatus)) {
            return false;
        }

        $this->update(['status' => $newStatus]);

        // Handle timestamp updates
        if ($newStatus === OrderStatus::DELIVERED) {
            $this->update(['delivered_at' => now()]);
        } elseif ($newStatus === OrderStatus::CANCELLED) {
            $this->update(['cancelled_at' => now()]);
        }

        // Log history
        $this->statusHistory()->create([
            'status' => $newStatus->value,
            'note' => $note,
            'changed_by' => $changedBy,
        ]);

        return true;
    }

    // ── Helpers ────────────────────────────────────────

    /**
     * Generate next sequential order number.
     * Format: SI-YYMMDD-NNNN (e.g. SI-240901-0001)
     */
    public static function generateOrderNumber(): string
    {
        $dateKey = now()->format('ymd');
        $prefix = 'SI-' . $dateKey . '-';
        $seq = \App\Services\SequenceService::next('order_' . $dateKey);

        return $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }

    public function isExpress(): bool
    {
        return $this->delivery_type === DeliveryType::EXPRESS;
    }

    public function isPaid(): bool
    {
        return $this->payment && $this->payment->status->value === 'captured';
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, [
            OrderStatus::NEW,
            OrderStatus::PAYMENT_PENDING,
            OrderStatus::PAID,
            OrderStatus::PROCESSING,
        ]);
    }

    public function getItemsCountAttribute(): int
    {
        return $this->items->sum('quantity');
    }
}
