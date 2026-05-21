<?php

namespace App\Services;

use App\Enums\StockLogAdjustmentType;
use App\Models\Distributor;
use App\Models\Product;
use App\Models\StockLog;
use App\Models\StockMovement;
use App\Models\User;
use App\Support\ProductInventoryService;
use App\Support\StockMovementLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class DistributorStockLogService
{
    /**
     * @return array{products: \Illuminate\Support\Collection, history: \Illuminate\Support\Collection, distributor: Distributor}
     */
    public function adjustFormData(User $distributorUser, Distributor $distributor): array
    {
        $region = $distributorUser->priceRegion();

        $products = Product::query()
            ->active()
            ->forPricingRegion($region)
            ->with('inventory')
            ->orderBy('name')
            ->get()
            ->map(fn (Product $product) => [
                'id' => $product->id,
                'name' => $product->name,
                'stock' => $product->stockLevel(),
            ]);

        $history = StockLog::query()
            ->forDistributor($distributor->id)
            ->with(['product:id,name', 'approver:id,name'])
            ->latest()
            ->limit(50)
            ->get();

        return [
            'distributor' => $distributor,
            'products' => $products,
            'history' => $history,
            'priceRegion' => $region,
        ];
    }

    public function submit(User $distributorUser, Distributor $distributor, array $data): StockLog
    {
        $product = Product::query()->findOrFail($data['product_id']);
        $type = StockLogAdjustmentType::from($data['adjustment_type']);
        $quantity = (int) $data['quantity'];

        if ($quantity < 1) {
            throw ValidationException::withMessages([
                'quantity' => 'Quantity must be at least 1.',
            ]);
        }

        if ($type === StockLogAdjustmentType::Deduct) {
            $current = $product->stockLevel();
            if ($quantity > $current) {
                throw ValidationException::withMessages([
                    'quantity' => "Cannot request deduction of {$quantity} units; only {$current} in stock.",
                ]);
            }
        }

        return StockLog::query()->create([
            'distributor_id' => $distributor->id,
            'product_id' => $product->id,
            'adjustment_type' => $type,
            'quantity' => $quantity,
            'reason' => $data['reason'],
            'remarks' => trim($data['remarks']),
            'approved_by' => null,
            'approved_at' => null,
        ]);
    }

    public function approve(StockLog $stockLog, User $admin): void
    {
        if (! $stockLog->isPending()) {
            throw ValidationException::withMessages([
                'stock_log' => 'This adjustment was already processed.',
            ]);
        }

        DB::transaction(function () use ($stockLog, $admin): void {
            $stockLog->load('distributor');
            $product = Product::query()->lockForUpdate()->findOrFail($stockLog->product_id);

            $inventoryType = $stockLog->adjustment_type === StockLogAdjustmentType::Add
                ? 'increase'
                : 'decrease';

            $result = ProductInventoryService::applyAdjustment(
                $product,
                $inventoryType,
                $stockLog->quantity,
            );

            StockMovementLogger::log(
                $product,
                $admin,
                StockMovement::ACTION_DISTRIBUTOR_ADJUSTMENT,
                $result['before'],
                $result['after'],
                sprintf(
                    'Distributor stock log #%d (%s): %s — %s',
                    $stockLog->id,
                    $stockLog->distributor->name,
                    $stockLog->adjustment_type->label(),
                    $stockLog->remarks,
                ),
            );

            $stockLog->update([
                'approved_by' => $admin->id,
                'approved_at' => now(),
            ]);
        });
    }

    public function reject(StockLog $stockLog): void
    {
        if (! $stockLog->isPending()) {
            throw ValidationException::withMessages([
                'stock_log' => 'This adjustment was already processed.',
            ]);
        }

        $stockLog->delete();
    }
}
