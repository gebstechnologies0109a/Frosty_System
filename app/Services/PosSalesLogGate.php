<?php

namespace App\Services;

use App\Models\SecurityLog;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;

final class PosSalesLogGate
{
    private const SESSION_KEY = 'pos_sales_logs_unlocked';

    private const MAX_ATTEMPTS = 3;

    private const LOCK_MINUTES = 10;

    public function isUnlocked(): bool
    {
        return (bool) session(self::SESSION_KEY);
    }

    public function unlock(): void
    {
        session([self::SESSION_KEY => true]);
    }

    public function lock(): void
    {
        session()->forget(self::SESSION_KEY);
    }

    public function isLocked(?User $user): bool
    {
        return Cache::has($this->lockKey($user));
    }

    public function remainingLockMinutes(?User $user): int
    {
        $expires = Cache::get($this->lockKey($user));

        if (! $expires) {
            return 0;
        }

        return max(0, (int) ceil(($expires - now()->timestamp) / 60));
    }

    public function unlockSession(User $user): void
    {
        Cache::forget($this->attemptKey($user));
        $this->unlock();
        SecurityLog::record($user, 'pos_logs_unlocked', 'Successful POS logs access');
    }

    /** @return bool True if account is now locked after this failure */
    public function recordFailedAttempt(User $user): bool
    {
        $attempts = (int) Cache::get($this->attemptKey($user), 0) + 1;
        Cache::put($this->attemptKey($user), $attempts, now()->addMinutes(self::LOCK_MINUTES));

        SecurityLog::record($user, 'pos_logs_failed_attempt', "Attempt {$attempts}/".self::MAX_ATTEMPTS);

        if ($attempts >= self::MAX_ATTEMPTS) {
            Cache::put($this->lockKey($user), now()->addMinutes(self::LOCK_MINUTES)->timestamp, now()->addMinutes(self::LOCK_MINUTES));
            SecurityLog::record($user, 'pos_logs_locked', 'Account locked after failed attempts');

            return true;
        }

        return false;
    }

    public function attempt(User $user, string $password): bool
    {
        if ($this->isLocked($user)) {
            return false;
        }

        if (Hash::check($password, $user->password)) {
            $this->unlockSession($user);

            return true;
        }

        $this->recordFailedAttempt($user);

        return false;
    }

    private function attemptKey(?User $user): string
    {
        return 'pos_logs_attempts:'.($user?->id ?? request()->ip());
    }

    private function lockKey(?User $user): string
    {
        return 'pos_logs_locked:'.($user?->id ?? request()->ip());
    }
}
