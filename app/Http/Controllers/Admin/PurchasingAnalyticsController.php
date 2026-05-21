<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Services\PurchasingAnalyticsService;
use Illuminate\View\View;

class PurchasingAnalyticsController extends Controller
{
    public function index(PurchasingAnalyticsService $analytics): View
    {
        abort_unless(auth()->user()?->role === UserRole::PurchasingAdmin, 403);

        return view('purchasing.analytics.index', [
            'analytics' => $analytics->build(),
        ]);
    }
}
