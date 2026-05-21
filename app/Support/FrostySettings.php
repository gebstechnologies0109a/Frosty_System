<?php

namespace App\Support;

use App\Models\SystemSetting;

final class FrostySettings
{
    public static function qualificationPoints(): int
    {
        return (int) (SystemSetting::get('qualification_points') ?? config('frosty.qualification_points'));
    }

    public static function pesoPerPoint(): float
    {
        return (float) (SystemSetting::get('peso_per_point') ?? config('frosty.peso_per_point'));
    }

    public static function overridePercent(int $level): float
    {
        $key = "override_level_{$level}_percent";
        $stored = SystemSetting::get($key);

        if ($stored !== null) {
            return (float) $stored;
        }

        return (float) config("frosty.override_percentages.{$level}", 0);
    }

    public static function currentMonth(): string
    {
        return now()->format('Y-m');
    }
}
