<?php

namespace App\Enums;

enum DeliveryType: string
{
    case EXPRESS = 'express';
    case STANDARD = 'standard';
    case PICKUP = 'pickup';

    public function label(): string
    {
        return match ($this) {
            self::EXPRESS => 'Ahmedabad Express (30-90 min)',
            self::STANDARD => 'Standard Delivery (1-2 days)',
            self::PICKUP => 'Showroom Pickup',
        };
    }
}
