<?php

namespace App\Models;

use App\Enums\StockLogAdjustmentType;
use App\Enums\StockLogReason;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockLog extends Model
{
    protected $fillable = [
        'distributor_id',
        'product_id',
        'adjustment_type',
        'quantity',
        'reason',
        'remarks',
        'approved_by',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'adjustment_type' => StockLogAdjustmentType::class,
            'reason' => StockLogReason::class,
            'quantity' => 'integer',
            'approved_at' => 'datetime',
        ];
    }

    public function distributor(): BelongsTo
    {
        return $this->belongsTo(Distributor::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function isPending(): bool
    {
        return $this->approved_by === null;
    }

    public function isApproved(): bool
    {
        return $this->approved_by !== null;
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->whereNull('approved_by');
    }

    public function scopeForDistributor(Builder $query, int $distributorId): Builder
    {
        return $query->where('distributor_id', $distributorId);
    }

    public function signedQuantity(): int
    {
        return match ($this->adjustment_type) {
            StockLogAdjustmentType::Add => $this->quantity,
            StockLogAdjustmentType::Deduct => -$this->quantity,
        };
    }

    public function statusLabel(): string
    {
        return $this->isPending() ? 'Pending approval' : 'Approved';
    }
}
