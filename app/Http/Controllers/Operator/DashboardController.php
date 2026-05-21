<?php

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Qualification;
use App\Services\WalletService;
use App\Support\FrostySettings;
use Illuminate\Support\Facades\Auth;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function __invoke(WalletService $wallets): View
    {
        $operator = Auth::user();
        $month = FrostySettings::currentMonth();

        return view('operator.dashboard', [
            'wallet' => $wallets->ensureWallet($operator),
            'qualification' => Qualification::query()
                ->where('user_id', $operator->id)
                ->where('month', $month)
                ->first(),
            'threshold' => FrostySettings::qualificationPoints(),
            'recentOrders' => Order::query()->where('user_id', $operator->id)->latest()->limit(5)->get(),
        ]);
    }
}
