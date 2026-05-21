<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PointLedgerType;
use App\Enums\WithdrawalStatus;
use App\Http\Controllers\Controller;
use App\Models\PointsLedger;
use App\Models\Wallet;
use App\Models\Withdrawal;
use App\Services\WalletService;
use App\Support\FrostySettings;
use App\Support\ListPage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class FinanceController extends Controller
{
    public function wallets(Request $request): View
    {
        return view('admin.finance.wallets', [
            'wallets' => Wallet::query()->with('user')->orderByDesc('balance')
                ->paginate(ListPage::perPage($request, 20))
                ->withQueryString(),
        ]);
    }

    public function rebates(Request $request): View
    {
        return view('admin.finance.rebates', [
            'entries' => PointsLedger::query()->with(['user', 'sourceUser', 'order'])->latest()
                ->paginate(ListPage::perPage($request, 20))
                ->withQueryString(),
        ]);
    }

    public function overrides(Request $request): View
    {
        return view('admin.finance.overrides', [
            'entries' => PointsLedger::query()
                ->where('type', PointLedgerType::Override)
                ->with(['user', 'sourceUser', 'order'])
                ->latest()
                ->paginate(ListPage::perPage($request, 20))
                ->withQueryString(),
        ]);
    }

    public function withdrawals(Request $request): View
    {
        return view('admin.finance.withdrawals', [
            'withdrawals' => Withdrawal::query()->with('user')->latest()
                ->paginate(ListPage::perPage($request, 20))
                ->withQueryString(),
        ]);
    }

    public function reports(): View
    {
        $month = FrostySettings::currentMonth();

        return view('admin.finance.reports', [
            'month' => $month,
            'selfTotal' => (float) PointsLedger::query()->where('month', $month)->where('type', PointLedgerType::Self)->sum('pesos'),
            'overrideTotal' => (float) PointsLedger::query()->where('month', $month)->where('type', PointLedgerType::Override)->sum('pesos'),
            'byLevel' => PointsLedger::query()
                ->where('month', $month)
                ->where('type', PointLedgerType::Override)
                ->select('level', DB::raw('SUM(pesos) as total'))
                ->groupBy('level')
                ->pluck('total', 'level'),
            'pendingWithdrawals' => Withdrawal::query()->where('status', WithdrawalStatus::Pending)->count(),
        ]);
    }

    public function approveWithdrawal(Request $request, Withdrawal $withdrawal, WalletService $wallets): RedirectResponse
    {
        if ($withdrawal->status !== WithdrawalStatus::Pending) {
            return back()->withErrors(['withdrawal' => 'Already processed.']);
        }

        $wallets->debit($withdrawal->user, (float) $withdrawal->amount, 'withdrawal', $withdrawal->id);
        $withdrawal->update([
            'status' => WithdrawalStatus::Approved,
            'processed_by' => $request->user()->id,
        ]);

        return back()->with('success', 'Withdrawal approved.');
    }

    public function rejectWithdrawal(Request $request, Withdrawal $withdrawal): RedirectResponse
    {
        $withdrawal->update([
            'status' => WithdrawalStatus::Rejected,
            'processed_by' => $request->user()->id,
            'notes' => $request->input('notes'),
        ]);

        return back()->with('success', 'Withdrawal rejected.');
    }
}
