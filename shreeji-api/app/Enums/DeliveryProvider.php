<?php

namespace App\Enums;

enum DeliveryProvider: string
{
    case PORTER = 'porter';
    case MANUAL = 'manual';
    case PICKUP = 'pickup';

    public function label(): string
    {
        return match ($this) {
            self::PORTER => 'Porter (Ahmedabad Express)',
            self::MANUAL => 'Manual Delivery',
            self::PICKUP => 'Showroom Pickup',
        };
    }
}
