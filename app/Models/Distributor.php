<?php

namespace App\Models;

use App\Enums\DistributorPricingRegion;
use App\Enums\PriceRegion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Distributor extends Model
{
    protected $fillable = [
        'name',
        'is_main',
        'user_id',
        'pricing_region',
    ];

    protected function casts(): array
    {
        return [
            'is_main' => 'boolean',
            'pricing_region' => DistributorPricingRegion::class,
        ];
    }

    public function pricingRegion(): DistributorPricingRegion
    {
        return $this->pricing_region ?? DistributorPricingRegion::Luzon;
    }

    public function operatorPriceRegion(): PriceRegion
    {
        return $this->pricingRegion()->toPriceRegion();
    }

    public function syncAssignedOperatorsPricingRegion(): void
    {
        $this->assignedOperators()->update([
            'region' => $this->operatorPriceRegion(),
        ]);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assignedOperators(): HasMany
    {
        return $this->hasMany(User::class, 'distributor_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function scopeMain($query)
    {
        return $query->where('is_main', true);
    }

    public static function mainId(): int
    {
        return (int) config('frosty.main_distributor_id', 1);
    }
}
