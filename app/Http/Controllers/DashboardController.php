<?php

namespace App\Http\Controllers;

use App\Models\KiloPurchase;
use App\Models\Member;
use App\Models\MonthlyMemberSummary;
use App\Models\PointLedger;
use App\Support\FrostyRules;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $now = now();
        $year = (int) $now->year;
        $month = (int) $now->month;

        return view('dashboard', [
            'rules' => [
                'points_per_kilo' => FrostyRules::POINTS_PER_KILO,
                'qualification_kilos' => FrostyRules::MONTHLY_QUALIFICATION_KILOS,
                'override_per_kilo' => FrostyRules::OVERRIDE_POINTS_PER_KILO,
            ],
            'stats' => [
                'members' => Member::query()->count(),
                'purchases_this_month' => KiloPurchase::query()
                    ->whereYear('purchased_at', $year)
                    ->whereMonth('purchased_at', $month)
                    ->count(),
                'kilos_this_month' => (float) KiloPurchase::query()
                    ->whereYear('purchased_at', $year)
                    ->whereMonth('purchased_at', $month)
                    ->sum('kilos'),
                'points_this_month' => (float) PointLedger::query()
                    ->where('period_year', $year)
                    ->where('period_month', $month)
                    ->sum('points'),
                'qualified_members' => MonthlyMemberSummary::query()
                    ->where('year', $year)
                    ->where('month', $month)
                    ->where('override_qualified', true)
                    ->count(),
            ],
            'recentPurchases' => KiloPurchase::query()
                ->with(['store', 'member'])
                ->latest('purchased_at')
                ->limit(10)
                ->get(),
            'monthlySummaries' => MonthlyMemberSummary::query()
                ->with('member')
                ->where('year', $year)
                ->where('month', $month)
                ->orderByDesc('total_kilos')
                ->limit(10)
                ->get(),
        ]);
    }
}
