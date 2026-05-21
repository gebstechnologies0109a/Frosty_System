<?php

namespace App\Http\Controllers\Operator;

use App\Enums\WithdrawalStatus;
use App\Http\Controllers\Controller;
use App\Models\PointsLedger;
use App\Models\Withdrawal;
use App\Services\WalletService;
use App\Support\ListPage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Contracts\View\View;

class WalletController extends Controller
{
    public function index(WalletService $wallets): View
    {
        $user = Auth::user();

        return view('operator.wallet', [
            'wallet' => $wallets->ensureWallet($user),
            'withdrawals' => Withdrawal::query()->where('user_id', $user->id)->latest()->get(),
        ]);
    }

    public function rebates(Request $request): View
    {
        return view('operator.rebates', [
            'entries' => PointsLedger::query()
                ->where('user_id', Auth::id())
                ->with(['sourceUser', 'order'])
                ->latest()
                ->paginate(ListPage::perPage($request, 20))
                ->withQueryString(),
        ]);
    }

    public function withdraw(Request $request, WalletService $wallets): RedirectResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:100'],
        ]);

        $user = Auth::user();
        $wallet = $wallets->ensureWallet($user);

        if ($wallet->balance < $data['amount']) {
            return back()->withErrors(['amount' => 'Insufficient balance.']);
        }

        Withdrawal::query()->create([
            'user_id' => $user->id,
            'amount' => $data['amount'],
            'status' => WithdrawalStatus::Pending,
        ]);

        return back()->with('success', 'Withdrawal request submitted.');
    }
}
