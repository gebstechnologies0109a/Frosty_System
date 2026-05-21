<?php

namespace App\Models;

use App\Enums\OrderSource;
use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Enums\PaymentMethod;
use App\Enums\PriceRegion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $fillable = [
        'user_id',
        'operator_id',
        'distributor_id',
        'status',
        'total_amount',
        'payment_method',
        'payment_proof_path',
        'notes',
        'total_points',
        'cogs_total',
        'gross_profit',
        'source',
        'order_type',
        'price_region',
        'approved_by',
        'approved_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'source' => OrderSource::class,
            'order_type' => OrderType::class,
            'payment_method' => PaymentMethod::class,
            'price_region' => PriceRegion::class,
            'total_amount' => 'decimal:2',
            'cogs_total' => 'decimal:2',
            'gross_profit' => 'decimal:2',
            'total_points' => 'integer',
            'approved_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function operator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'operator_id');
    }

    public function distributor(): BelongsTo
    {
        return $this->belongsTo(Distributor::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function pointsLedger(): HasMany
    {
        return $this->hasMany(PointsLedger::class);
    }

    public function isRoutedToMain(): bool
    {
        if (! $this->distributor_id) {
            return false;
        }

        if ($this->relationLoaded('distributor') && $this->distributor) {
            return (bool) $this->distributor->is_main;
        }

        return (bool) Distributor::query()
            ->whereKey($this->distributor_id)
            ->value('is_main');
    }

    public function scopePending($query)
    {
        return $query->where('status', OrderStatus::Pending);
    }

    public function scopeForPurchasingQueue($query)
    {
        return $query->pending()->where('distributor_id', Distributor::mainId());
    }

    public function scopePos($query)
    {
        return $query->where('order_type', OrderType::Pos);
    }

    public function scopeSupply($query)
    {
        return $query->where(function ($q) {
            $q->where('order_type', OrderType::Supply)
                ->orWhereNull('order_type');
        });
    }

    public function isPos(): bool
    {
        return $this->order_type === OrderType::Pos;
    }

    public function requiresPaymentProof(): bool
    {
        if ($this->isPos()) {
            return false;
        }

        return (bool) config('frosty.require_payment_proof', true);
    }

    public function hasPaymentProof(): bool
    {
        return filled($this->payment_proof_path);
    }

    public function paymentProofUrl(): ?string
    {
        return app(\App\Services\OrderPaymentProofService::class)->url($this->payment_proof_path);
    }

    public function paymentProofIsPdf(): bool
    {
        return app(\App\Services\OrderPaymentProofService::class)->isPdf($this->payment_proof_path);
    }

    public function paymentProofIsImage(): bool
    {
        return app(\App\Services\OrderPaymentProofService::class)->isImage($this->payment_proof_path);
    }
}
