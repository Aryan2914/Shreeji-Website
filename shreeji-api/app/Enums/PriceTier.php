<?php

namespace App\Enums;

enum PriceTier: string
{
    case RETAIL = 'retail';
    case BUSINESS = 'business';
    case BULK = 'bulk';

    public function label(): string
    {
        return match ($this) {
            self::RETAIL => 'Retail',
            self::BUSINESS => 'Business',
            self::BULK => 'Bulk',
        };
    }
}
