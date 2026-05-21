<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\StockLog;
use App\Services\DistributorStockLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AdminStockLogController extends Controller
{
    public function approve(Request $request, StockLog $stockLog, DistributorStockLogService $service): RedirectResponse
    {
        abort_unless(
            in_array($request->user()?->role, [UserRole::SuperAdmin, UserRole::PurchasingAdmin], true),
            403,
        );

        $service->approve($stockLog, $request->user());

        return back()->with('success', 'Stock adjustment approved and applied.');
    }

    public function reject(Request $request, StockLog $stockLog, DistributorStockLogService $service): RedirectResponse
    {
        abort_unless(
            in_array($request->user()?->role, [UserRole::SuperAdmin, UserRole::PurchasingAdmin], true),
            403,
        );

        $service->reject($stockLog);

        return back()->with('success', 'Stock adjustment request rejected.');
    }
}
