<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\PosDailyClosing;
use App\Models\User;
use App\Services\PosDailyClosingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminPosDailyClosingController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless(auth()->user()?->role === UserRole::SuperAdmin, 403);

        $query = PosDailyClosing::query()
            ->with(['operator:id,name,email', 'approver:id,name'])
            ->orderByDesc('closing_date')
            ->orderByDesc('created_at');

        if ($request->filled('operator_id')) {
            $query->where('operator_id', $request->input('operator_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('closing_date', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('closing_date', '<=', $request->input('date_to'));
        }

        return view('admin.pos-daily-closings.index', [
            'closings' => $query->paginate(25)->withQueryString(),
            'operators' => User::query()->where('role', UserRole::Operator)->orderBy('name')->get(['id', 'name']),
            'filters' => $request->only(['operator_id', 'status', 'date_from', 'date_to']),
        ]);
    }

    public function approve(PosDailyClosing $posDailyClosing, PosDailyClosingService $service): RedirectResponse
    {
        abort_unless(auth()->user()?->role === UserRole::SuperAdmin, 403);

        $service->approve($posDailyClosing, auth()->user());

        return back()->with('success', 'Daily closing approved.');
    }

    public function reject(PosDailyClosing $posDailyClosing, PosDailyClosingService $service): RedirectResponse
    {
        abort_unless(auth()->user()?->role === UserRole::SuperAdmin, 403);

        $service->reject($posDailyClosing, auth()->user());

        return back()->with('success', 'Daily closing rejected.');
    }

    public function reopen(PosDailyClosing $posDailyClosing, PosDailyClosingService $service): RedirectResponse
    {
        abort_unless(auth()->user()?->role === UserRole::SuperAdmin, 403);

        $service->reopen($posDailyClosing);

        return back()->with('success', 'Day lock removed. Operator may submit a new closing or record sales for that date.');
    }
}
