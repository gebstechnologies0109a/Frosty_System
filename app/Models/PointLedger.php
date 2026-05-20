<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PointLedger extends Model
{
    protected $table = 'point_ledger';

    protected $fillable = [
        'member_id',
        'type',
        'points',
        'kilos_basis',
        'source_member_id',
        'kilo_purchase_id',
        'period_year',
        'period_month',
    ];

    protected function casts(): array
    {
        return [
            'points' => 'decimal:2',
            'kilos_basis' => 'decimal:2',
        ];
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function sourceMember(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'source_member_id');
    }

    public function kiloPurchase(): BelongsTo
    {
        return $this->belongsTo(KiloPurchase::class);
    }
}
