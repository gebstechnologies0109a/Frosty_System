<?php

namespace App\Enums;

enum StockLogReason: string
{
    case Count = 'count';
    case Damage = 'damage';
    case Transfer = 'transfer';
    case Correction = 'correction';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Count => 'Physical count',
            self::Damage => 'Damage / spoilage',
            self::Transfer => 'Transfer',
            self::Correction => 'Correction',
            self::Other => 'Other',
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
