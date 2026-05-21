<?php

namespace App\Http\Controllers\Distributor;

use App\Enums\StockLogAdjustmentType;
use App\Enums\StockLogReason;
use App\Http\Controllers\Controller;
use App\Services\DistributorDashboardService;
use App\Services\DistributorStockLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
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

    public function adjust(DistributorStockLogService $stockLogs): View
    {
        $user = Auth::user();
        $profile = $user->distributorProfile;

        if (! $profile) {
            abort(403, 'No distributor profile linked to this account.');
        }

        return view('distributor.inventory.adjust', $stockLogs->adjustFormData($user, $profile));
    }

    public function storeAdjustment(Request $request, DistributorStockLogService $stockLogs): RedirectResponse
    {
        $user = Auth::user();
        $profile = $user->distributorProfile;

        if (! $profile) {
            abort(403, 'No distributor profile linked to this account.');
        }

        $validated = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'adjustment_type' => ['required', Rule::in(StockLogAdjustmentType::values())],
            'quantity' => ['required', 'integer', 'min:1'],
            'reason' => ['required', Rule::in(StockLogReason::values())],
            'remarks' => ['required', 'string', 'min:3', 'max:2000'],
        ]);

        $stockLogs->submit($user, $profile, $validated);

        return redirect()
            ->route('distributor.inventory.adjust')
            ->with('success', 'Adjustment submitted for admin approval.');
    }
}
