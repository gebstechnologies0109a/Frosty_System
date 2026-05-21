<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SecurityLog extends Model
{
    protected $fillable = [
        'user_id',
        'event',
        'ip_address',
        'details',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function record(?User $user, string $event, ?string $details = null): self
    {
        return self::query()->create([
            'user_id' => $user?->id,
            'event' => $event,
            'ip_address' => request()->ip(),
            'details' => $details,
        ]);
    }
}
