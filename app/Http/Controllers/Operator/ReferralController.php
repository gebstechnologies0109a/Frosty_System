<?php

namespace App\Http\Controllers\Operator;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\GenealogyEngine;
use App\Services\OperatorProductDefaultsService;
use App\Services\WalletService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class ReferralController extends Controller
{
    public function create(): View
    {
        return view('operator.referrals.create');
    }

    public function store(Request $request, GenealogyEngine $genealogy, WalletService $wallets, OperatorProductDefaultsService $productDefaults): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $sponsor = Auth::user();

        $operator = User::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => UserRole::Operator,
            'status' => UserStatus::Active,
            'distributor_id' => $sponsor->distributor_id,
        ]);

        $genealogy->assignGenealogy($operator, $sponsor);
        $wallets->ensureWallet($operator);
        $productDefaults->ensureDefaults($operator);

        return redirect()->route('operator.genealogy')->with('success', 'Operator referral created.');
    }
}
