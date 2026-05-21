<?php

namespace App\Http\Controllers\Distributor;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $user = Auth::user();
        $profile = $user->distributorProfile;

        if (! $profile) {
            abort(403, 'No distributor profile linked to this account.');
        }

        return view('distributor.dashboard', [
            'distributor' => $profile,
            'operators' => User::query()
                ->where('distributor_id', $profile->id)
                ->where('role', UserRole::Operator)
                ->orderBy('name')
                ->get(),
            'pendingOperatorOrders' => Order::query()
                ->where('distributor_id', $profile->id)
                ->pending()
                ->with('user')
                ->latest()
                ->limit(10)
                ->get(),
            'myOrders' => Order::query()
                ->where('user_id', $user->id)
                ->latest()
                ->limit(5)
                ->get(),
        ]);
    }
}
