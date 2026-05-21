<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\PosDailyClosing;
use App\Models\Product;
use App\Models\User;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('admin.dashboard', [
            'stats' => [
                'operators' => User::query()->where('role', 'operator')->count(),
                'distributors' => User::query()->where('role', 'distributor')->count(),
                'users' => User::query()->count(),
                'products' => Product::query()->count(),
                'pos_logs' => Order::query()->pos()->count(),
                'pos_closings' => PosDailyClosing::query()->count(),
            ],
            'recentOrders' => Order::query()
                ->with(['user', 'distributor'])
                ->latest()
                ->limit(15)
                ->get(),
        ]);
    }
}
