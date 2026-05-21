<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Services\AdminPosSalesLogService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PosSalesLogController extends Controller
{
    public function index(Request $request, AdminPosSalesLogService $logs): View
    {
        abort_unless(auth()->user()?->role === UserRole::SuperAdmin, 403);

        $data = $logs->indexData($request);

        return view('admin.pos-sales-logs.index', [
            'orders' => $data['orders'],
            'operators' => $data['operators'],
            'filters' => $data['filters'],
        ]);
    }

    public function export(Request $request, AdminPosSalesLogService $logs): StreamedResponse
    {
        abort_unless(auth()->user()?->role === UserRole::SuperAdmin, 403);

        return $logs->exportCsv($request);
    }
}
