<?php

namespace App\Models;

use App\Enums\PriceRegion;
use App\Enums\ProductCategory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Product extends Model
{
    protected $fillable = [
        'name',
        'category',
        'points',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'category' => ProductCategory::class,
            'points' => 'integer',
        ];
    }

    public function prices(): HasMany
    {
        return $this->hasMany(ProductPrice::class);
    }

    public function inventory(): HasOne
    {
        return $this->hasOne(ProductInventory::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class)->latest('created_at');
    }

    public function stockLevel(): int
    {
        $this->loadMissing('inventory');

        return $this->inventory?->stock ?? 0;
    }

    public function priceForRegion(PriceRegion|string $region): float
    {
        $key = $region instanceof PriceRegion ? $region->value : $region;

        $this->loadMissing('prices');

        $match = $this->prices->first(fn (ProductPrice $p) => $p->region->value === $key)
            ?? $this->prices->first(fn (ProductPrice $p) => $p->region === PriceRegion::Luzon);

        return (float) ($match?->price ?? 0);
    }

    /** @return array<string, float> */
    public function regionalPrices(): array
    {
        $this->loadMissing('prices');

        $out = [];
        foreach (PriceRegion::cases() as $region) {
            $out[$region->value] = $this->priceForRegion($region);
        }

        return $out;
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    /** Products that have a price row for the given catalog region. */
    public function scopeForPricingRegion(Builder $query, PriceRegion|string $region): Builder
    {
        $key = $region instanceof PriceRegion ? $region->value : $region;

        return $query->whereHas('prices', fn (Builder $q) => $q->where('region', $key));
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
