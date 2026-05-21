<?php

namespace App\Services;

use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;

final class WalletService
{
    public function ensureWallet(User $user): Wallet
    {
        return Wallet::query()->firstOrCreate(
            ['user_id' => $user->id],
            ['balance' => 0],
        );
    }

    public function credit(User $user, float $pesos, string $referenceType, ?int $referenceId = null): Wallet
    {
        return DB::transaction(function () use ($user, $pesos, $referenceType, $referenceId) {
            $wallet = $this->ensureWallet($user);
            $wallet->increment('balance', $pesos);

            WalletTransaction::query()->create([
                'wallet_id' => $wallet->id,
                'user_id' => $user->id,
                'amount' => $pesos,
                'balance_after' => $wallet->fresh()->balance,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'created_at' => now(),
            ]);

            return $wallet->fresh();
        });
    }

    public function debit(User $user, float $pesos, string $referenceType, ?int $referenceId = null): Wallet
    {
        return DB::transaction(function () use ($user, $pesos, $referenceType, $referenceId) {
            $wallet = $this->ensureWallet($user);

            if ((float) $wallet->balance < $pesos) {
                throw new \RuntimeException('Insufficient wallet balance.');
            }

            $wallet->decrement('balance', $pesos);

            WalletTransaction::query()->create([
                'wallet_id' => $wallet->id,
                'user_id' => $user->id,
                'amount' => -$pesos,
                'balance_after' => $wallet->fresh()->balance,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'created_at' => now(),
            ]);

            return $wallet->fresh();
        });
    }
}
