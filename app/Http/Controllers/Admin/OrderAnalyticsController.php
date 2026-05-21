<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Distributor;
use App\Services\OrderAnalyticsFilter;
use App\Services\OrderAnalyticsService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderAnalyticsController extends Controller
{
    private const ALLOWED_ROLES = [
        UserRole::SuperAdmin,
        UserRole::FinanceAdmin,
        UserRole::PurchasingAdmin,
    ];

    public function index(Request $request, OrderAnalyticsService $analytics): View
    {
        $role = auth()->user()?->role;
        abort_unless($role && in_array($role, self::ALLOWED_ROLES, true), 403);

        OrderAnalyticsFilter::validated($request);

        return view('admin.orders.analytics.index', [
            'analytics' => $analytics->build($request),
            'filters' => $request->only(['date_from', 'date_to', 'region', 'distributor_id', 'status']),
            'distributors' => Distributor::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }
}
