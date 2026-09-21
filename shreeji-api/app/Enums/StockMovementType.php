<?php

namespace App\Enums;

enum StockMovementType: string
{
    case PURCHASE = 'purchase';
    case SALE = 'sale';
    case RETURN = 'return';
    case ADJUSTMENT = 'adjustment';
    case RESERVED = 'reserved';
    case RELEASED = 'released';

    public function label(): string
    {
        return match ($this) {
            self::PURCHASE => 'Purchase / Restock',
            self::SALE => 'Sale',
            self::RETURN => 'Return',
            self::ADJUSTMENT => 'Manual Adjustment',
            self::RESERVED => 'Reserved for Order',
            self::RELEASED => 'Released (order cancelled/expired)',
        };
    }

    /**
     * Whether this movement type adds or removes stock.
     */
    public function direction(): int
    {
        return match ($this) {
            self::PURCHASE, self::RETURN, self::RELEASED => 1,   // Stock increases
            self::SALE, self::RESERVED => -1,                      // Stock decreases
            self::ADJUSTMENT => 0,                                  // Can be either (sign in quantity)
        };
    }
}
