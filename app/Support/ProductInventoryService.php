<?php

namespace App\Support;

use App\Models\Product;
use App\Models\ProductInventory;

final class ProductInventoryService
{
    public static function ensure(Product $product): ProductInventory
    {
        return ProductInventory::query()->firstOrCreate(
            ['product_id' => $product->id],
            ['stock' => 0],
        );
    }

    /**
     * @return array{before: int, after: int}
     */
    public static function applyAdjustment(Product $product, string $type, int $amount): array
    {
        $inventory = self::ensure($product);
        $stock = (int) $inventory->stock;

        $newStock = match ($type) {
            'increase' => $stock + $amount,
            'decrease' => max($stock - $amount, 0),
            'set' => max($amount, 0),
            default => $stock,
        };

        $inventory->update(['stock' => $newStock]);

        return ['before' => $stock, 'after' => $newStock];
    }
}
