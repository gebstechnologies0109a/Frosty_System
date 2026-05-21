<?php

namespace App\Support;

use App\Enums\PriceRegion;
use App\Models\Product;

final class ProductRegionalPricing
{
    /**
     * @param  array<string, float|int|string>  $prices  Keys: luzon, davao, tacloban (or price_luzon, etc.)
     */
    public static function sync(Product $product, array $prices): void
    {
        foreach (PriceRegion::cases() as $region) {
            $value = $prices[$region->value]
                ?? $prices['price_'.$region->value]
                ?? null;

            if ($value === null) {
                continue;
            }

            $product->prices()->updateOrCreate(
                ['region' => $region->value],
                ['price' => round((float) $value, 2)],
            );
        }
    }

    /** @return array<string, float> */
    public static function pricesFromRequest(array $input): array
    {
        return [
            PriceRegion::Luzon->value => (float) ($input['price_luzon'] ?? $input['luzon'] ?? 0),
            PriceRegion::Davao->value => (float) ($input['price_davao'] ?? $input['davao'] ?? 0),
            PriceRegion::Tacloban->value => (float) ($input['price_tacloban'] ?? $input['tacloban'] ?? 0),
        ];
    }

    /**
     * @param  array<string, float|null>  $overrides  luzon/davao/tacloban keys; null values ignored
     */
    public static function applyPartial(Product $product, array $overrides = [], ?float $percentChange = null): void
    {
        $product->loadMissing('prices');
        $current = $product->regionalPrices();

        if ($percentChange !== null) {
            $multiplier = 1 + ($percentChange / 100);
            foreach (PriceRegion::cases() as $region) {
                $current[$region->value] = round($current[$region->value] * $multiplier, 2);
            }
        }

        foreach (PriceRegion::cases() as $region) {
            $key = $region->value;
            if (array_key_exists($key, $overrides) && $overrides[$key] !== null && $overrides[$key] !== '') {
                $current[$key] = round((float) $overrides[$key], 2);
            }
        }

        self::sync($product, $current);
    }
}
