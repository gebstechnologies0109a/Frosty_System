<?php

namespace App\Enums;

enum ProductCategory: string
{
    case Softserve = 'softserve';
    case Yogurt = 'yogurt';
    case Syrup = 'syrup';
    case Dip = 'dip';
    case Beverage = 'beverage';
    case Coffee = 'coffee';
    case Supply = 'supply';
    case Sparepart = 'sparepart';
    case Ramen = 'ramen';
    case Cone = 'cone';

    /** Categories that earn rebate points (softserve only). */
    public function earnsRebatePoints(): bool
    {
        return $this === self::Softserve;
    }

    public function label(): string
    {
        return match ($this) {
            self::Softserve => 'Softserve',
            self::Yogurt => 'Yogurt',
            self::Syrup => 'Syrup',
            self::Dip => 'Dip',
            self::Beverage => 'Beverage',
            self::Coffee => 'Coffee',
            self::Supply => 'Supply',
            self::Sparepart => 'Spare Part',
            self::Ramen => 'Ramen',
            self::Cone => 'Cone',
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
