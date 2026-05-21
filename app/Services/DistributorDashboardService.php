<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\PriceRegion;
use App\Enums\UserRole;
use App\Models\Distributor;
use App\Models\OperatorInventory;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

final class DistributorDashboardService
{
    /** @return array<string, mixed> */
    public function build(User $distributorUser, Distributor $distributor): array
    {
        $region = $distributorUser->priceRegion();
        $operatorIds = $this->operatorIds($distributor);
        $since30 = now()->subDays(30)->startOfDay();

        $orders30 = Order::query()
            ->whereIn('user_id', $operatorIds)
            ->where('created_at', '>=', $since30);

        $pendingOrders = Order::query()
            ->where('distributor_id', $distributor->id)
            ->pending()
            ->with('user')
            ->latest()
            ->limit(10)
            ->get();

        $operators = User::query()
            ->where('distributor_id', $distributor->id)
            ->where('role', UserRole::Operator)
            ->withCount([
                'orders as orders_30_count' => fn ($q) => $q->where('created_at', '>=', $since30),
            ])
            ->orderBy('name')
            ->get();

        $inventorySummary = $this->inventorySummary($operatorIds);
        $regionalProducts = Product::query()
            ->forPricingRegion($region)
            ->with(['prices' => fn ($q) => $q->where('region', $region)])
            ->with('inventory')
            ->orderBy('name')
            ->limit(12)
            ->get()
            ->map(fn (Product $product) => [
                'id' => $product->id,
                'name' => $product->name,
                'category' => $product->category->label(),
                'price' => $product->priceForRegion($region),
                'points' => $product->points,
                'stock' => $product->stockLevel(),
            ]);

        return [
            'distributor' => $distributor,
            'pricingRegion' => $distributor->pricingRegion(),
            'priceRegion' => $region,
            'summary' => [
                'operators' => $operators->count(),
                'pending_orders' => $pendingOrders->count(),
                'orders_30' => (int) (clone $orders30)->count(),
                'order_value_30' => (float) (clone $orders30)->sum('total_amount'),
                'low_stock_skus' => $inventorySummary['low_stock_skus'],
                'out_of_stock_skus' => $inventorySummary['out_of_stock_skus'],
            ],
            'operators' => $operators,
            'pendingOrders' => $pendingOrders,
            'myOrders' => Order::query()
                ->where('user_id', $distributorUser->id)
                ->latest()
                ->limit(5)
                ->get(),
            'inventorySummary' => $inventorySummary,
            'regionalProducts' => $regionalProducts,
            'chart' => $this->orderChart($operatorIds, now()->subDays(13)->startOfDay(), now()),
        ];
    }

    /** @return Collection<int, int> */
    private function operatorIds(Distributor $distributor): Collection
    {
        return User::query()
            ->where('distributor_id', $distributor->id)
            ->where('role', UserRole::Operator)
            ->pluck('id');
    }

    /**
     * @param  Collection<int, int>  $operatorIds
     * @return array<string, mixed>
     */
    private function inventorySummary(Collection $operatorIds): array
    {
        if ($operatorIds->isEmpty()) {
            return [
                'rows' => collect(),
                'low_stock_skus' => 0,
                'out_of_stock_skus' => 0,
                'total_units' => 0,
            ];
        }

        $rows = OperatorInventory::query()
            ->whereIn('operator_id', $operatorIds)
            ->with(['product:id,name', 'operator:id,name'])
            ->orderBy('stock')
            ->limit(15)
            ->get()
            ->map(fn (OperatorInventory $row) => [
                'operator' => $row->operator?->name ?? '—',
                'product' => $row->product?->name ?? '—',
                'stock' => $row->stock,
                'status' => $row->stockStatusLabel(),
                'status_key' => $row->stockStatus(),
            ]);

        $all = OperatorInventory::query()->whereIn('operator_id', $operatorIds)->get();

        return [
            'rows' => $rows,
            'low_stock_skus' => $all->filter(fn (OperatorInventory $r) => $r->stockStatus() === 'low_stock')->count(),
            'out_of_stock_skus' => $all->filter(fn (OperatorInventory $r) => $r->stockStatus() === 'out_of_stock')->count(),
            'total_units' => (int) $all->sum('stock'),
        ];
    }

    /**
     * @param  Collection<int, int>  $operatorIds
     * @return array{labels: list<string>, values: list<int>}
     */
    private function orderChart(Collection $operatorIds, Carbon $start, Carbon $end): array
    {
        if ($operatorIds->isEmpty()) {
            return ['labels' => [], 'values' => []];
        }

        $daily = Order::query()
            ->whereIn('user_id', $operatorIds)
            ->where('status', OrderStatus::Approved)
            ->where('created_at', '>=', $start)
            ->selectRaw('DATE(created_at) as day, COUNT(*) as total')
            ->groupBy('day')
            ->orderBy('day')
            ->pluck('total', 'day');

        $labels = [];
        $values = [];
        for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
            $key = $d->format('Y-m-d');
            $labels[] = $d->format('M j');
            $values[] = (int) ($daily[$key] ?? 0);
        }

        return ['labels' => $labels, 'values' => $values];
    }

    /** @return array<string, mixed> */
    public function inventoryPage(User $distributorUser, Distributor $distributor): array
    {
        $region = $distributorUser->priceRegion();
        $operatorIds = $this->operatorIds($distributor);

        $products = Product::query()
            ->forPricingRegion($region)
            ->with(['prices' => fn ($q) => $q->where('region', $region), 'inventory'])
            ->orderBy('name')
            ->get();

        $operatorStock = OperatorInventory::query()
            ->whereIn('operator_id', $operatorIds)
            ->with(['product:id,name', 'operator:id,name'])
            ->orderByDesc('stock')
            ->get();

        return [
            'distributor' => $distributor,
            'pricingRegion' => $distributor->pricingRegion(),
            'priceRegion' => $region,
            'products' => $products,
            'operatorStock' => $operatorStock,
        ];
    }
}
