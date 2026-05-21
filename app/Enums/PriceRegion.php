<?php

namespace App\Enums;

enum PriceRegion: string
{
    case Luzon = 'luzon';
    case Davao = 'davao';
    case Tacloban = 'tacloban';

    public function label(): string
    {
        return match ($this) {
            self::Luzon => 'Luzon',
            self::Davao => 'Davao',
            self::Tacloban => 'Tacloban',
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
