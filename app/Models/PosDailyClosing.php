<?php

namespace App\Models;

use App\Enums\PosDailyClosingStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PosDailyClosing extends Model
{
    protected $fillable = [
        'operator_id',
        'closing_date',
        'total_sales',
        'total_cogs',
        'gross_profit',
        'gross_margin_percent',
        'expected_cash',
        'actual_cash',
        'variance',
        'notes',
        'status',
        'approved_by',
    ];

    protected function casts(): array
    {
        return [
            'closing_date' => 'date',
            'total_sales' => 'decimal:2',
            'total_cogs' => 'decimal:2',
            'gross_profit' => 'decimal:2',
            'gross_margin_percent' => 'decimal:2',
            'expected_cash' => 'decimal:2',
            'actual_cash' => 'decimal:2',
            'variance' => 'decimal:2',
            'status' => PosDailyClosingStatus::class,
        ];
    }

    public function operator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'operator_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function locksPosDay(): bool
    {
        return in_array($this->status, [
            PosDailyClosingStatus::Pending,
            PosDailyClosingStatus::Approved,
            PosDailyClosingStatus::Rejected,
        ], true);
    }
}
