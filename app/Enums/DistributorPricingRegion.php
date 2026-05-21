<?php

namespace App\Enums;

enum DistributorPricingRegion: string
{
    case Luzon = 'luzon';
    case Mindanao = 'mindanao';

    public function label(): string
    {
        return match ($this) {
            self::Luzon => 'Luzon',
            self::Mindanao => 'Mindanao',
        };
    }

    /** Maps distributor pricing to operator/catalog price region. */
    public function toPriceRegion(): PriceRegion
    {
        return match ($this) {
            self::Luzon => PriceRegion::Luzon,
            self::Mindanao => PriceRegion::Davao,
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
