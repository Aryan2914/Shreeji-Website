<?php

namespace App\Enums;

enum OrderStatus: string
{
    case NEW = 'new';
    case PAYMENT_PENDING = 'payment_pending';
    case PAID = 'paid';
    case PROCESSING = 'processing';
    case PACKING = 'packing';
    case READY_FOR_PICKUP = 'ready_for_pickup';
    case PORTER_ASSIGNED = 'porter_assigned';
    case OUT_FOR_DELIVERY = 'out_for_delivery';
    case DELIVERED = 'delivered';
    case CANCELLED = 'cancelled';
    case RETURN_REQUESTED = 'return_requested';
    case RETURNED = 'returned';
    case REFUNDED = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::NEW => 'New Order',
            self::PAYMENT_PENDING => 'Payment Pending',
            self::PAID => 'Paid',
            self::PROCESSING => 'Processing',
            self::PACKING => 'Packing',
            self::READY_FOR_PICKUP => 'Ready for Pickup',
            self::PORTER_ASSIGNED => 'Porter Assigned',
            self::OUT_FOR_DELIVERY => 'Out for Delivery',
            self::DELIVERED => 'Delivered',
            self::CANCELLED => 'Cancelled',
            self::RETURN_REQUESTED => 'Return Requested',
            self::RETURNED => 'Returned',
            self::REFUNDED => 'Refunded',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::NEW => 'info',
            self::PAYMENT_PENDING => 'warning',
            self::PAID => 'success',
            self::PROCESSING => 'info',
            self::PACKING => 'info',
            self::READY_FOR_PICKUP => 'primary',
            self::PORTER_ASSIGNED => 'primary',
            self::OUT_FOR_DELIVERY => 'primary',
            self::DELIVERED => 'success',
            self::CANCELLED => 'danger',
            self::RETURN_REQUESTED => 'warning',
            self::RETURNED => 'danger',
            self::REFUNDED => 'gray',
        };
    }

    /**
     * Get the valid next statuses from the current status.
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::NEW => [self::PAYMENT_PENDING, self::CANCELLED],
            self::PAYMENT_PENDING => [self::PAID, self::CANCELLED],
            self::PAID => [self::PROCESSING, self::CANCELLED],
            self::PROCESSING => [self::PACKING, self::CANCELLED],
            self::PACKING => [self::READY_FOR_PICKUP, self::CANCELLED],
            self::READY_FOR_PICKUP => [self::PORTER_ASSIGNED, self::OUT_FOR_DELIVERY, self::CANCELLED],
            self::PORTER_ASSIGNED => [self::OUT_FOR_DELIVERY, self::CANCELLED],
            self::OUT_FOR_DELIVERY => [self::DELIVERED, self::CANCELLED],
            self::DELIVERED => [self::RETURN_REQUESTED],
            self::RETURN_REQUESTED => [self::RETURNED, self::DELIVERED],
            self::CANCELLED => [self::PAID], // Allowed only when reviving order with captured payment if stock available
            self::RETURNED => [self::REFUNDED],
            self::REFUNDED => [],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions());
    }
}
