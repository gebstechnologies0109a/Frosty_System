<?php

namespace App\Models;

use App\Enums\PointLedgerType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property int|null $source_user_id
 * @property int $level
 * @property int $points
 * @property float $pesos
 * @property \App\Enums\PointLedgerType $type
 * @property string $month
 * @property int|null $order_id
 */
class PointsLedger extends Model
{
    protected $table = 'points_ledger';

    protected $fillable = [
        'user_id',
        'source_user_id',
        'level',
        'points',
        'pesos',
        'type',
        'month',
        'order_id',
    ];

    protected function casts(): array
    {
        return [
            'type' => PointLedgerType::class,
            'pesos' => 'decimal:2',
            'level' => 'integer',
            'points' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        /** @var \Illuminate\Database\Eloquent\Relations\BelongsTo<User, PointsLedger> */
        return $this->belongsTo(User::class);
    }

    public function sourceUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'source_user_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
