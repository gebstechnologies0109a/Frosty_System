<?php

namespace App\Http\Controllers\Distributor;

use App\Http\Controllers\Controller;
use App\Services\DistributorAnalyticsService;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DistributorAnalyticsController extends Controller
{
    public function index(DistributorAnalyticsService $analytics): View
    {
        $user = Auth::user();
        $profile = $user->distributorProfile;

        if (! $profile) {
            abort(403, 'No distributor profile linked to this account.');
        }

        return view('distributor.analytics.index', [
            'distributor' => $profile,
            'analytics' => $analytics->build($profile, $user),
        ]);
    }
}
