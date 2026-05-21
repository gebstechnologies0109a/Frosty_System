<?php

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Qualification;
use App\Services\OperatorDashboardService;
use App\Services\WalletService;
use App\Support\FrostySettings;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function __invoke(OperatorDashboardService $dashboard, WalletService $wallets): View
    {
        $operator = Auth::user();
        $month = FrostySettings::currentMonth();
        $metrics = $dashboard->metrics($operator);

        return view('operator.dashboard', [
            'wallet' => $wallets->ensureWallet($operator),
            'qualification' => Qualification::query()
                ->where('user_id', $operator->id)
                ->where('month', $month)
                ->first(),
            'threshold' => FrostySettings::qualificationPoints(),
            'recentOrders' => Order::query()->where('user_id', $operator->id)->latest()->limit(5)->get(),
            'cards' => $metrics['cards'],
            'today' => $metrics['today'],
            'dayLocked' => $metrics['day_locked'],
        ]);
    }
}
