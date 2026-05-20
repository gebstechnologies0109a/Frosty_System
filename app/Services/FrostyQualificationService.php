<?php

namespace App\Services;

use App\Models\KiloPurchase;
use App\Models\Member;
use App\Models\MonthlyMemberSummary;
use App\Support\FrostyRules;
use Carbon\Carbon;

final class FrostyQualificationService
{
    public function monthlyKilos(Member $member, ?Carbon $at = null): float
    {
        $at ??= now();
        $start = $at->copy()->startOfMonth();
        $end = $at->copy()->endOfMonth();

        return (float) KiloPurchase::query()
            ->where('member_id', $member->id)
            ->whereBetween('purchased_at', [$start, $end])
            ->sum('kilos');
    }

    public function isQualifiedForOverride(Member $member, ?Carbon $at = null): bool
    {
        return $this->monthlyKilos($member, $at) >= FrostyRules::MONTHLY_QUALIFICATION_KILOS;
    }

    public function syncMonthlySummary(Member $member, ?Carbon $at = null): MonthlyMemberSummary
    {
        $at ??= now();
        $year = (int) $at->year;
        $month = (int) $at->month;

        $summary = MonthlyMemberSummary::query()->firstOrNew([
            'member_id' => $member->id,
            'year' => $year,
            'month' => $month,
        ]);

        $start = $at->copy()->startOfMonth();
        $end = $at->copy()->endOfMonth();

        $summary->total_kilos = (float) KiloPurchase::query()
            ->where('member_id', $member->id)
            ->whereBetween('purchased_at', [$start, $end])
            ->sum('kilos');

        $ledger = $member->pointLedger()
            ->where('period_year', $year)
            ->where('period_month', $month);

        $summary->total_direct_points = (float) (clone $ledger)
            ->where('type', FrostyRules::TYPE_DIRECT)
            ->sum('points');

        $summary->total_override_points = (float) (clone $ledger)
            ->whereIn('type', [
                FrostyRules::TYPE_OVERRIDE_L2,
                FrostyRules::TYPE_OVERRIDE_L3,
                FrostyRules::TYPE_OVERRIDE_L4,
            ])
            ->sum('points');

        $summary->override_qualified = $summary->total_kilos >= FrostyRules::MONTHLY_QUALIFICATION_KILOS;
        $summary->save();

        return $summary;
    }
}
