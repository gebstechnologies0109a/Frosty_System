<?php

namespace App\Http\Controllers\Admin;

use App\Enums\WithdrawalStatus;
use App\Http\Controllers\Controller;
use App\Models\WalletTransaction;
use App\Models\Withdrawal;
use App\Services\WalletService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Support\ListPage;
use Illuminate\View\View;

class AdminPendingWithdrawalController extends Controller
{
    public function index(Request $request): View
    {
        $query = Withdrawal::query()
            ->where('status', WithdrawalStatus::Pending)
            ->with('user:id,name,email');

        if ($request->filled('q')) {
            $q = '%'.$request->string('q').'%';
            $query->whereHas('user', fn ($u) => $u->where('name', 'like', $q)->orWhere('email', 'like', $q));
        }

        return view('admin.withdrawals.pending', [
            'withdrawals' => $query->latest()->paginate(ListPage::perPage($request, 20))->withQueryString(),
        ]);
    }

    public function show(Withdrawal $withdrawal): View
    {
        abort_unless($withdrawal->status === WithdrawalStatus::Pending, 404);

        $withdrawal->load('user.wallet');

        $walletLogs = WalletTransaction::query()
            ->where('user_id', $withdrawal->user_id)
            ->latest('created_at')
            ->limit(50)
            ->get();

        return view('admin.withdrawals.show', compact('withdrawal', 'walletLogs'));
    }

    public function approve(Request $request, Withdrawal $withdrawal, WalletService $wallets): RedirectResponse
    {
        if ($withdrawal->status !== WithdrawalStatus::Pending) {
            return back()->withErrors(['withdrawal' => 'Already processed.']);
        }

        $wallets->debit($withdrawal->user, (float) $withdrawal->amount, 'withdrawal', $withdrawal->id);
        $withdrawal->update([
            'status' => WithdrawalStatus::Approved,
            'processed_by' => $request->user()->id,
        ]);

        return redirect()
            ->route('admin.withdrawals.pending')
            ->with('success', 'Withdrawal approved.');
    }

    public function reject(Request $request, Withdrawal $withdrawal): RedirectResponse
    {
        $request->validate(['notes' => ['nullable', 'string', 'max:1000']]);

        $withdrawal->update([
            'status' => WithdrawalStatus::Rejected,
            'processed_by' => $request->user()->id,
            'notes' => $request->input('notes'),
        ]);

        return redirect()
            ->route('admin.withdrawals.pending')
            ->with('success', 'Withdrawal rejected.');
    }
}
