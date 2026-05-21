<?php

namespace App\Services;

use App\Models\OperatorProduct;
use App\Models\User;

final class OperatorProductDefaultsService
{
    /** @var list<array{product_name: string, price: float, cost: float|null}> */
    public const DEFAULTS = [
        ['product_name' => 'Cone Twirl', 'price' => 15, 'cost' => null],
        ['product_name' => 'Giant Cone Twirl', 'price' => 25, 'cost' => null],
        ['product_name' => 'Sundae', 'price' => 35, 'cost' => null],
    ];

    public function ensureDefaults(User $operator): void
    {
        foreach (self::DEFAULTS as $item) {
            OperatorProduct::query()->firstOrCreate(
                [
                    'operator_id' => $operator->id,
                    'product_name' => $item['product_name'],
                    'is_system_default' => true,
                ],
                [
                    'price' => $item['price'],
                    'cost' => $item['cost'],
                    'status' => 'active',
                    'description' => null,
                ],
            );
        }
    }

    public function backfillAllOperators(): void
    {
        User::query()
            ->where('role', \App\Enums\UserRole::Operator)
            ->each(fn (User $operator) => $this->ensureDefaults($operator));
    }
}
