<?php

namespace App\Models;

use App\Enums\WithdrawalStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property float $amount
 * @property \App\Enums\WithdrawalStatus $status
 * @property string|null $notes
 */
class Withdrawal extends Model
{
    protected $fillable = [
        'user_id',
        'amount',
        'status',
        'notes',
        'processed_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'status' => WithdrawalStatus::class,
        ];
    }

    public function user(): BelongsTo
    {
        /** @var \Illuminate\Database\Eloquent\Relations\BelongsTo<User, Withdrawal> */
        return $this->belongsTo(User::class);
    }

    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }
}
