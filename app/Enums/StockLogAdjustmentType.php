<?php

namespace App\Enums;

enum StockLogAdjustmentType: string
{
    case Add = 'add';
    case Deduct = 'deduct';

    public function label(): string
    {
        return match ($this) {
            self::Add => 'Add stock',
            self::Deduct => 'Deduct stock',
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
