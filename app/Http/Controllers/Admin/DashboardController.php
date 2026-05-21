<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\Withdrawal;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('admin.dashboard', [
            'stats' => [
                'operators' => User::query()->where('role', 'operator')->count(),
                'distributors' => User::query()->where('role', 'distributor')->count(),
                'products' => Product::query()->count(),
                'pending_orders' => Order::query()->where('status', 'pending')->count(),
                'pending_withdrawals' => Withdrawal::query()->where('status', 'pending')->count(),
            ],
        ]);
    }
}
