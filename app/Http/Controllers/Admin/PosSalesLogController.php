<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Services\AdminPosSalesLogService;
use App\Services\PosSalesLogGate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PosSalesLogController extends Controller
{
    public function index(Request $request, AdminPosSalesLogService $logs, PosSalesLogGate $gate): View|RedirectResponse
    {
        abort_unless(auth()->user()?->role === UserRole::SuperAdmin, 403);

        if (! $gate->isUnlocked()) {
            return redirect()->route('admin.pos-sales-logs.secure');
        }

        $data = $logs->indexData($request);

        return view('admin.pos-sales-logs.index', [
            'orders' => $data['orders'],
            'operators' => $data['operators'],
            'filters' => $data['filters'],
        ]);
    }

    public function secure(PosSalesLogGate $gate): View|RedirectResponse
    {
        abort_unless(auth()->user()?->role === UserRole::SuperAdmin, 403);

        if ($gate->isUnlocked()) {
            return redirect()->route('admin.pos-sales-logs.index');
        }

        return view('admin.pos-sales-logs.secure', [
            'locked' => $gate->isLocked(auth()->user()),
            'lockMinutes' => $gate->remainingLockMinutes(auth()->user()),
        ]);
    }

    public function unlock(Request $request, PosSalesLogGate $gate): RedirectResponse
    {
        abort_unless(auth()->user()?->role === UserRole::SuperAdmin, 403);

        if ($gate->isLocked(auth()->user())) {
            return back()->withErrors(['password' => 'Too many failed attempts. Try again in '.$gate->remainingLockMinutes(auth()->user()).' minutes.']);
        }

        $request->validate(['password' => ['required', 'string']]);

        $user = auth()->user();

        if (! Hash::check($request->input('password'), $user->password)) {
            if (! $gate->recordFailedAttempt($user)) {
                return back()->withErrors(['password' => 'Too many failed attempts. Try again in '.$gate->remainingLockMinutes($user).' minutes.']);
            }

            return back()->withErrors(['password' => 'Incorrect password.']);
        }

        $gate->unlockSession($user);

        return redirect()->route('admin.pos-sales-logs.index');
    }

    public function export(Request $request, AdminPosSalesLogService $logs, PosSalesLogGate $gate): StreamedResponse|RedirectResponse
    {
        abort_unless(auth()->user()?->role === UserRole::SuperAdmin, 403);

        if (! $gate->isUnlocked()) {
            return redirect()->route('admin.pos-sales-logs.secure');
        }

        return $logs->exportCsv($request);
    }
}
