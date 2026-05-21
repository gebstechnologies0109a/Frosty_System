<?php

namespace App\Services;

use App\Enums\ProductCategory;
use App\Models\OperatorInventory;
use App\Models\Product;
use App\Models\User;
use App\Support\StockMovementLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

final class OperatorInventoryService
{
    /** @var list<string> */
    public const SUPPLY_CATEGORIES = [
        'softserve', 'yogurt', 'syrup', 'dip', 'cone', 'beverage', 'coffee', 'supply', 'sparepart',
    ];

    /** @var list<string> */
    public const CATEGORY_ORDER = [
        'softserve', 'yogurt', 'syrup', 'dip', 'cone', 'beverage', 'coffee', 'supply', 'sparepart',
    ];

    public function ensureSupplyRows(User $operator): void
    {
        $productIds = Product::query()
            ->active()
            ->whereIn('category', self::SUPPLY_CATEGORIES)
            ->pluck('id');

        $existing = OperatorInventory::query()
            ->where('operator_id', $operator->id)
            ->pluck('product_id');

        $missing = $productIds->diff($existing);

        foreach ($missing as $productId) {
            OperatorInventory::query()->create([
                'operator_id' => $operator->id,
                'product_id' => $productId,
                'stock' => 0,
            ]);
        }
    }

    /** @return array<string, mixed> */
    public function indexData(User $operator, Request $request): array
    {
        $this->ensureSupplyRows($operator);

        $query = OperatorInventory::query()
            ->where('operator_id', $operator->id)
            ->with('product')
            ->whereHas('product', fn ($q) => $q->active()->whereIn('category', self::SUPPLY_CATEGORIES));

        if ($request->filled('category')) {
            $query->whereHas('product', fn ($q) => $q->where('category', $request->input('category')));
        }

        $rows = $query->get()
            ->sortBy(fn (OperatorInventory $row) => $row->product->name)
            ->values();

        if ($request->filled('stock_status')) {
            $rows = $rows->filter(fn (OperatorInventory $row) => $row->stockStatus() === $request->input('stock_status'));
        }

        $grouped = [];
        foreach (self::CATEGORY_ORDER as $cat) {
            $items = $rows->filter(fn (OperatorInventory $row) => $row->product->category->value === $cat);
            if ($items->isEmpty()) {
                continue;
            }
            $grouped[] = [
                'category' => $cat,
                'label' => ProductCategory::tryFrom($cat)?->label() ?? ucfirst($cat),
                'items' => $items->values(),
            ];
        }

        return [
            'grouped' => $grouped,
            'categories' => collect(self::CATEGORY_ORDER)
                ->mapWithKeys(fn ($c) => [$c => ProductCategory::tryFrom($c)?->label() ?? ucfirst($c)])
                ->all(),
            'filters' => $request->only(['category', 'stock_status']),
        ];
    }

    public function adjust(User $operator, int $productId, string $mode, int $amount, ?int $minimumStock = null): OperatorInventory
    {
        $product = Product::query()
            ->active()
            ->whereIn('category', self::SUPPLY_CATEGORIES)
            ->findOrFail($productId);

        $row = OperatorInventory::query()->firstOrCreate(
            ['operator_id' => $operator->id, 'product_id' => $product->id],
            ['stock' => 0],
        );

        $before = $row->stock;
        $after = match ($mode) {
            'increase' => $before + max(0, $amount),
            'decrease' => max(0, $before - max(0, $amount)),
            'set' => max(0, $amount),
            default => throw new \InvalidArgumentException('Invalid adjustment mode.'),
        };

        $row->stock = $after;
        if ($minimumStock !== null) {
            $row->minimum_stock = max(0, $minimumStock);
        }
        $row->save();

        StockMovementLogger::logOperatorAdjustment($product, $operator, $before, $after);

        return $row->load('product');
    }
}
