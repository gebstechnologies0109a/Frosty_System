<?php

namespace App\Services;

use App\Enums\PointLedgerType;
use App\Models\PointsLedger;
use App\Models\Qualification;
use App\Models\User;
use App\Support\FrostySettings;

final class QualificationEngine
{
    public function monthFor(?\DateTimeInterface $at = null): string
    {
        return ($at ?? now())->format('Y-m');
    }

    public function recordPersonalPoints(User $operator, int $points, ?string $month = null): Qualification
    {
        $month ??= FrostySettings::currentMonth();

        $qualification = Qualification::query()->firstOrNew([
            'user_id' => $operator->id,
            'month' => $month,
        ]);

        $qualification->personal_points = (int) ($qualification->personal_points ?? 0) + $points;
        $wasQualified = $qualification->qualified;

        $qualification->qualified = $qualification->personal_points >= FrostySettings::qualificationPoints();

        if ($qualification->qualified && ! $wasQualified) {
            $qualification->qualified_at = now();
        }

        $qualification->save();

        return $qualification;
    }

    public function isQualified(User $operator, ?string $month = null): bool
    {
        $month ??= FrostySettings::currentMonth();

        return Qualification::query()
            ->where('user_id', $operator->id)
            ->where('month', $month)
            ->where('qualified', true)
            ->exists();
    }

    public function rebuildFromLedger(User $operator, ?string $month = null): Qualification
    {
        $month ??= FrostySettings::currentMonth();

        $points = (int) PointsLedger::query()
            ->where('user_id', $operator->id)
            ->where('month', $month)
            ->where('level', 0)
            ->where('type', PointLedgerType::Self)
            ->sum('points');

        $qualified = $points >= FrostySettings::qualificationPoints();

        return Qualification::query()->updateOrCreate(
            ['user_id' => $operator->id, 'month' => $month],
            [
                'personal_points' => $points,
                'qualified' => $qualified,
                'qualified_at' => $qualified ? now() : null,
            ],
        );
    }
}
