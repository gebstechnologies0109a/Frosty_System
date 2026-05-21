<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class AdminImpersonationService
{
    public const SESSION_IMPERSONATOR = 'impersonator_id';

    public function impersonate(User $admin, User $target): void
    {
        if ($admin->role !== UserRole::SuperAdmin) {
            throw new RuntimeException('Only Super Admin can impersonate users.');
        }

        if ($target->role === UserRole::SuperAdmin) {
            throw new RuntimeException('Cannot impersonate another Super Admin.');
        }

        if ($target->id === $admin->id) {
            throw new RuntimeException('Cannot impersonate yourself.');
        }

        session([self::SESSION_IMPERSONATOR => $admin->id]);
        Auth::login($target);
    }

    public function stop(): void
    {
        $impersonatorId = session(self::SESSION_IMPERSONATOR);

        if (! $impersonatorId) {
            return;
        }

        $admin = User::query()->find($impersonatorId);

        session()->forget(self::SESSION_IMPERSONATOR);

        if ($admin && $admin->role === UserRole::SuperAdmin) {
            Auth::login($admin);
        }
    }

    public function isImpersonating(): bool
    {
        return session()->has(self::SESSION_IMPERSONATOR);
    }

    public function forceLogout(User $user): void
    {
        if (config('session.driver') === 'database') {
            DB::table(config('session.table', 'sessions'))
                ->where('user_id', $user->id)
                ->delete();
        }
    }
}
