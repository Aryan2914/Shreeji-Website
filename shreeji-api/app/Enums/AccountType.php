<?php

namespace App\Enums;

enum AccountType: string
{
    case PERSONAL = 'personal';
    case BUSINESS = 'business';

    public function label(): string
    {
        return match ($this) {
            self::PERSONAL => 'Personal',
            self::BUSINESS => 'Business',
        };
    }
}
