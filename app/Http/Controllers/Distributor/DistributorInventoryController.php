<?php

namespace App\Http\Controllers\Distributor;

use App\Http\Controllers\Controller;
use App\Services\DistributorDashboardService;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DistributorInventoryController extends Controller
{
    public function index(DistributorDashboardService $dashboard): View
    {
        $user = Auth::user();
        $profile = $user->distributorProfile;

        if (! $profile) {
            abort(403, 'No distributor profile linked to this account.');
        }

        return view('distributor.inventory.index', $dashboard->inventoryPage($user, $profile));
    }
}
