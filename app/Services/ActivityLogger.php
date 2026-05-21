<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;

final class ActivityLogger
{
    public function log(?User $user, string $action, array $meta = []): void
    {
        ActivityLog::query()->create([
            'user_id' => $user?->id,
            'action' => $action,
            'meta' => $meta,
            'ip_address' => request()->ip(),
        ]);
    }
}
