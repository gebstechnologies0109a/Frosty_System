<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Services\PurchasingAnalyticsService;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Throwable;

class PurchasingAnalyticsController extends Controller
{
    private const ALLOWED_ROLES = [
        UserRole::SuperAdmin,
        UserRole::PurchasingAdmin,
    ];

    public function index(PurchasingAnalyticsService $analytics): View
    {
        $role = auth()->user()?->role;
        abort_unless($role && in_array($role, self::ALLOWED_ROLES, true), 403);

        try {
            return view('purchasing.analytics.index', [
                'analytics' => $analytics->build(),
            ]);
        } catch (Throwable $e) {
            Log::error('Failed to load purchasing analytics', [
                'message' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            abort(500, 'Unable to load purchasing analytics. Please try again or contact support.');
        }
    }
}
