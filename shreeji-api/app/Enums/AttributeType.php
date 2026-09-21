<?php

namespace App\Enums;

enum AttributeType: string
{
    case TEXT = 'text';
    case NUMBER = 'number';
    case SELECT = 'select';
    case BOOLEAN = 'boolean';

    public function label(): string
    {
        return match ($this) {
            self::TEXT => 'Text',
            self::NUMBER => 'Number',
            self::SELECT => 'Select (Dropdown)',
            self::BOOLEAN => 'Yes/No',
        };
    }
}
