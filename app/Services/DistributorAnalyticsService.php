<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\ProductCategory;
use App\Enums\UserRole;
use App\Models\Distributor;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Qualification;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class DistributorAnalyticsService
{
    /** @return array<string, mixed> */
    public function build(Distributor $distributor, User $distributorUser): array
    {
        $now = Carbon::now();
        $days30 = $now->copy()->subDays(30);
        $days60 = $now->copy()->subDays(60);
        $monthKey = $now->format('Y-m');

        $operatorIds = $this->operatorIds($distributor);
        $region = $distributorUser->priceRegion()->value;

        $summary = $this->summary($operatorIds, $days30, $monthKey);
        $operatorPerformance = $this->operatorPerformance($operatorIds, $days30, $monthKey);
        $orderTrends = $this->orderTrends($operatorIds, $days30, $now, $region);
        $productPerformance = $this->productPerformance($operatorIds, $days30, $days60);
        $inventory = $this->inventoryRecommendations($operatorIds, $days30);
        $genealogy = $this->genealogyGrowth($distributor, $now);
        $pricing = $this->regionalPricingImpact($operatorIds, $days30, $region);

        return [
            'summary' => $summary,
            'operatorPerformance' => $operatorPerformance,
            'orderTrends' => $orderTrends,
            'productPerformance' => $productPerformance,
            'inventory' => $inventory,
            'genealogy' => $genealogy,
            'pricing' => $pricing,
            'region' => $region,
            'charts' => [
                'dailyOrders' => $orderTrends['dailyChart'],
                'monthlyOrders' => $orderTrends['monthlyChart'],
                'categoryBreakdown' => $productPerformance['categoryChart'],
                'softserveVsOther' => $productPerformance['softserveChart'],
                'genealogyGrowth' => $genealogy['growthChart'],
            ],
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
    private function summary(Collection $operatorIds, Carbon $days30, string $monthKey): array
    {
        $orders30 = $this->operatorOrdersQuery($operatorIds)->where('created_at', '>=', $days30);

        $softserveOrders = (clone $orders30)
            ->whereHas('items.product', fn ($q) => $q->where('category', ProductCategory::Softserve))
            ->count();

        $fastestLevel = $this->fastestGrowingLevel($operatorIds, $monthKey);

        return [
            'total_operators' => $operatorIds->count(),
            'orders_last_30_days' => (clone $orders30)->count(),
            'order_value_last_30_days' => (float) (clone $orders30)->sum('total_amount'),
            'softserve_orders_last_30_days' => $softserveOrders,
            'fastest_growing_level' => $fastestLevel,
        ];
    }

    /**
     * @param  Collection<int, int>  $operatorIds
     * @return array<string, mixed>
     */
    private function operatorPerformance(Collection $operatorIds, Carbon $days30, string $monthKey): array
    {
        $threshold = (int) config('frosty.qualification_points', 20);

        $topOperators = Order::query()
            ->whereIn('user_id', $operatorIds)
            ->where('created_at', '>=', $days30)
            ->select('user_id', DB::raw('COUNT(*) as order_count'), DB::raw('SUM(total_amount) as order_value'))
            ->groupBy('user_id')
            ->orderByDesc('order_count')
            ->limit(10)
            ->get()
            ->map(function ($row) {
                $user = User::query()->find($row->user_id);

                return [
                    'name' => $user?->name ?? 'Unknown',
                    'orders' => (int) $row->order_count,
                    'value' => (float) $row->order_value,
                ];
            });

        $activeIds = Order::query()
            ->whereIn('user_id', $operatorIds)
            ->where('created_at', '>=', $days30)
            ->distinct()
            ->pluck('user_id');

        $inactive = User::query()
            ->whereIn('id', $operatorIds)
            ->when($activeIds->isNotEmpty(), fn ($q) => $q->whereNotIn('id', $activeIds))
            ->orderBy('name')
            ->limit(15)
            ->get(['id', 'name']);

        $nearingQualification = Qualification::query()
            ->whereIn('user_id', $operatorIds)
            ->where('month', $monthKey)
            ->where('personal_points', '>=', 15)
            ->where('personal_points', '<', $threshold)
            ->with('user:id,name')
            ->get()
            ->map(fn ($q) => [
                'name' => $q->user?->name,
                'points' => $q->personal_points,
            ]);

        $qualified = Qualification::query()
            ->whereIn('user_id', $operatorIds)
            ->where('month', $monthKey)
            ->where('qualified', true)
            ->with('user:id,name')
            ->get()
            ->map(fn ($q) => [
                'name' => $q->user?->name,
                'points' => $q->personal_points,
            ]);

        return [
            'topOperators' => $topOperators,
            'inactiveOperators' => $inactive,
            'nearingQualification' => $nearingQualification,
            'qualifiedThisMonth' => $qualified,
        ];
    }

    /**
     * @param  Collection<int, int>  $operatorIds
     * @return array<string, mixed>
     */
    private function orderTrends(Collection $operatorIds, Carbon $days30, Carbon $now, string $region): array
    {
        $base = $this->operatorOrdersQuery($operatorIds);

        $daily = (clone $base)
            ->where('created_at', '>=', $days30)
            ->selectRaw('DATE(created_at) as day, COUNT(*) as total')
            ->groupBy('day')
            ->orderBy('day')
            ->pluck('total', 'day');

        $dailyLabels = [];
        $dailyValues = [];
        for ($d = $days30->copy(); $d->lte($now); $d->addDay()) {
            $key = $d->format('Y-m-d');
            $dailyLabels[] = $d->format('M j');
            $dailyValues[] = (int) ($daily[$key] ?? 0);
        }

        $monthly = (clone $base)
            ->where('created_at', '>=', $now->copy()->subMonths(12))
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as total")
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        $monthlyLabels = $monthly->pluck('month')->all();
        $monthlyValues = $monthly->pluck('total')->map(fn ($v) => (int) $v)->all();

        $regionDist = (clone $base)
            ->where('created_at', '>=', $days30)
            ->where('price_region', $region)
            ->count();

        $totalRegionOrders = (clone $base)->where('created_at', '>=', $days30)->count();

        return [
            'dailyChart' => ['labels' => $dailyLabels, 'values' => $dailyValues],
            'monthlyChart' => ['labels' => $monthlyLabels, 'values' => $monthlyValues],
            'monthly' => $monthly,
            'regionOrders' => $regionDist,
            'totalOrders' => $totalRegionOrders,
            'region' => ucfirst($region),
        ];
    }

    /**
     * @param  Collection<int, int>  $operatorIds
     * @return array<string, mixed>
     */
    private function productPerformance(Collection $operatorIds, Carbon $days30, Carbon $days60): array
    {
        $salesBase = OrderItem::query()
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->whereIn('orders.user_id', $operatorIds)
            ->where('orders.status', OrderStatus::Approved)
            ->where('orders.created_at', '>=', $days30);

        $topProducts = (clone $salesBase)
            ->select(
                'products.name',
                'products.category',
                DB::raw('SUM(order_items.qty) as units'),
                DB::raw('SUM(order_items.qty * order_items.price) as revenue'),
            )
            ->groupBy('products.id', 'products.name', 'products.category')
            ->orderByDesc('units')
            ->limit(10)
            ->get();

        $orderedProductIds = OrderItem::query()
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->whereIn('orders.user_id', $operatorIds)
            ->where('orders.status', OrderStatus::Approved)
            ->where('orders.created_at', '>=', $days60)
            ->distinct()
            ->pluck('order_items.product_id');

        $slowMoving = Product::query()
            ->where('status', 'active')
            ->when($orderedProductIds->isNotEmpty(), fn ($q) => $q->whereNotIn('id', $orderedProductIds))
            ->orderBy('name')
            ->limit(10)
            ->get(['name', 'category']);

        $categoryRows = (clone $salesBase)
            ->select('products.category', DB::raw('SUM(order_items.qty) as units'))
            ->groupBy('products.category')
            ->get();

        $pieLabels = [];
        $pieValues = [];
        foreach ($categoryRows as $row) {
            $pieLabels[] = ProductCategory::tryFrom($row->category)?->label() ?? $row->category;
            $pieValues[] = (int) $row->units;
        }

        $softserveUnits = (int) (clone $salesBase)
            ->where('products.category', ProductCategory::Softserve)
            ->sum('order_items.qty');

        $otherUnits = (int) (clone $salesBase)
            ->where('products.category', '!=', ProductCategory::Softserve)
            ->sum('order_items.qty');

        return [
            'topProducts' => $topProducts,
            'slowMoving' => $slowMoving,
            'categoryChart' => ['labels' => $pieLabels, 'values' => $pieValues],
            'softserveChart' => [
                'labels' => ['Softserve', 'Non-Softserve'],
                'values' => [$softserveUnits, $otherUnits],
            ],
        ];
    }

    /**
     * @param  Collection<int, int>  $operatorIds
     * @return array<string, mixed>
     */
    private function inventoryRecommendations(Collection $operatorIds, Carbon $days30): array
    {
        $frequent = OrderItem::query()
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->leftJoin('product_inventory', 'products.id', '=', 'product_inventory.product_id')
            ->whereIn('orders.user_id', $operatorIds)
            ->where('orders.created_at', '>=', $days30)
            ->select(
                'products.id',
                'products.name',
                DB::raw('SUM(order_items.qty) as units_ordered'),
                DB::raw('ROUND(SUM(order_items.qty) / 30) as avg_daily'),
                DB::raw('COALESCE(product_inventory.stock, 0) as stock'),
            )
            ->groupBy('products.id', 'products.name', 'product_inventory.stock')
            ->orderByDesc('units_ordered')
            ->limit(15)
            ->get()
            ->map(fn ($row) => [
                'name' => $row->name,
                'units_ordered' => (int) $row->units_ordered,
                'suggested_reorder' => max((int) ceil($row->units_ordered), (int) $row->avg_daily * 30),
                'current_stock' => (int) $row->stock,
            ]);

        $lowStock = DB::table('products')
            ->join('product_inventory', 'products.id', '=', 'product_inventory.product_id')
            ->where('product_inventory.stock', '>', 0)
            ->where('product_inventory.stock', '<', 10)
            ->select('products.name', 'product_inventory.stock')
            ->orderBy('product_inventory.stock')
            ->limit(15)
            ->get();

        $overStocked = DB::table('products')
            ->join('product_inventory', 'products.id', '=', 'product_inventory.product_id')
            ->where('product_inventory.stock', '>', 500)
            ->select('products.name', 'product_inventory.stock')
            ->orderByDesc('product_inventory.stock')
            ->limit(15)
            ->get();

        return [
            'frequent' => $frequent,
            'lowStock' => $lowStock,
            'overStocked' => $overStocked,
        ];
    }

    /** @return array<string, mixed> */
    private function genealogyGrowth(Distributor $distributor, Carbon $now): array
    {
        $monthStart = $now->copy()->startOfMonth();

        $perLevel = [];
        for ($level = 1; $level <= 4; $level++) {
            $perLevel[$level] = User::query()
                ->where('distributor_id', $distributor->id)
                ->where('role', UserRole::Operator)
                ->where('genealogy_level', $level)
                ->count();
        }

        $newThisMonth = User::query()
            ->where('distributor_id', $distributor->id)
            ->where('role', UserRole::Operator)
            ->where('created_at', '>=', $monthStart)
            ->count();

        $growthLabels = [];
        $growthValues = [];
        for ($i = 5; $i >= 0; $i--) {
            $m = $now->copy()->subMonths($i);
            $growthLabels[] = $m->format('M Y');
            $growthValues[] = User::query()
                ->where('distributor_id', $distributor->id)
                ->where('role', UserRole::Operator)
                ->whereYear('created_at', $m->year)
                ->whereMonth('created_at', $m->month)
                ->count();
        }

        $activeBranches = User::query()
            ->where('distributor_id', $distributor->id)
            ->where('role', UserRole::Operator)
            ->whereNotNull('sponsor_id')
            ->select('sponsor_id', DB::raw('COUNT(*) as referrals'))
            ->groupBy('sponsor_id')
            ->orderByDesc('referrals')
            ->limit(10)
            ->get()
            ->map(function ($row) {
                $sponsor = User::query()->find($row->sponsor_id);

                return [
                    'name' => $sponsor?->name ?? 'Unknown',
                    'referrals' => (int) $row->referrals,
                ];
            });

        return [
            'perLevel' => $perLevel,
            'newThisMonth' => $newThisMonth,
            'growthChart' => ['labels' => $growthLabels, 'values' => $growthValues],
            'activeBranches' => $activeBranches,
        ];
    }

    /**
     * @param  Collection<int, int>  $operatorIds
     * @return array<string, mixed>
     */
    private function regionalPricingImpact(Collection $operatorIds, Carbon $days30, string $region): array
    {
        $orders = $this->operatorOrdersQuery($operatorIds)
            ->where('created_at', '>=', $days30)
            ->get();

        $avgOrderValue = $orders->avg('total_amount') ?? 0;
        $regionOrders = $orders->where('price_region', $region);
        $avgRegionValue = $regionOrders->avg('total_amount') ?? 0;

        $topDiff = DB::table('products')
            ->leftJoin('product_prices as pl', fn ($j) => $j->on('products.id', '=', 'pl.product_id')->where('pl.region', 'luzon'))
            ->leftJoin('product_prices as pr', fn ($j) => $j->on('products.id', '=', 'pr.product_id')->where('pr.region', $region))
            ->where('products.status', 'active')
            ->whereRaw('COALESCE(pr.price, 0) > COALESCE(pl.price, 0)')
            ->select(
                'products.name',
                DB::raw('COALESCE(pl.price, 0) as luzon'),
                DB::raw('COALESCE(pr.price, 0) as regional'),
                DB::raw('COALESCE(pr.price, 0) - COALESCE(pl.price, 0) as diff'),
            )
            ->orderByDesc('diff')
            ->limit(10)
            ->get();

        return [
            'distributorRegion' => ucfirst($region),
            'avgOrderValue' => round((float) $avgOrderValue, 2),
            'avgRegionOrderValue' => round((float) $avgRegionValue, 2),
            'regionOrderCount' => $regionOrders->count(),
            'topPriceDifferences' => $topDiff,
        ];
    }

    /**
     * @param  Collection<int, int>  $operatorIds
     */
    private function operatorOrdersQuery(Collection $operatorIds)
    {
        if ($operatorIds->isEmpty()) {
            return Order::query()->whereRaw('1 = 0');
        }

        return Order::query()->whereIn('user_id', $operatorIds);
    }

    /**
     * @param  Collection<int, int>  $operatorIds
     * @return array{level: int, count: int}
     */
    private function fastestGrowingLevel(Collection $operatorIds, string $monthKey): array
    {
        $currentMonth = Carbon::createFromFormat('Y-m', $monthKey);
        $prevMonth = $currentMonth->copy()->subMonth()->format('Y-m');

        $best = ['level' => 1, 'count' => 0];

        for ($level = 1; $level <= 4; $level++) {
            $current = User::query()
                ->whereIn('id', $operatorIds)
                ->where('role', UserRole::Operator)
                ->where('genealogy_level', $level)
                ->whereRaw("DATE_FORMAT(created_at, '%Y-%m') = ?", [$monthKey])
                ->count();

            $previous = User::query()
                ->whereIn('id', $operatorIds)
                ->where('role', UserRole::Operator)
                ->where('genealogy_level', $level)
                ->whereRaw("DATE_FORMAT(created_at, '%Y-%m') = ?", [$prevMonth])
                ->count();

            $growth = $current - $previous;
            if ($growth > $best['count']) {
                $best = ['level' => $level, 'count' => $growth];
            }
        }

        return $best;
    }
}
