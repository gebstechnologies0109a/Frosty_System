<?php

namespace App\Support;

final class FrostyRules
{
    public const POINTS_PER_KILO = 2.0;

    public const MONTHLY_QUALIFICATION_KILOS = 20.0;

    public const OVERRIDE_POINTS_PER_KILO = 0.5;

    public const MAX_UPLINE_LEVELS = 4;

    public const TYPE_DIRECT = 'direct';

    public const TYPE_OVERRIDE_L2 = 'override_l2';

    public const TYPE_OVERRIDE_L3 = 'override_l3';

    public const TYPE_OVERRIDE_L4 = 'override_l4';

    public static function directPoints(float $kilos): float
    {
        return round($kilos * self::POINTS_PER_KILO, 2);
    }

    public static function overridePoints(float $kilos): float
    {
        return round($kilos * self::OVERRIDE_POINTS_PER_KILO, 2);
    }

    public static function transactionTypeForLevel(int $level): string
    {
        return match ($level) {
            1 => self::TYPE_DIRECT,
            2 => self::TYPE_OVERRIDE_L2,
            3 => self::TYPE_OVERRIDE_L3,
            4 => self::TYPE_OVERRIDE_L4,
            default => self::TYPE_DIRECT,
        };
    }

    public static function levelLabel(int $level): string
    {
        return match ($level) {
            1 => 'Direct (self)',
            2 => 'Override L2',
            3 => 'Override L3',
            4 => 'Override L4',
            default => 'Points',
        };
    }
}
