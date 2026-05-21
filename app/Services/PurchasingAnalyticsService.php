<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\ProductCategory;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\StockMovement;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class PurchasingAnalyticsService
{
    /** @var list<string> */
    private const CATEGORY_ORDER = [
        'softserve', 'yogurt', 'syrup', 'dip', 'cone',
        'beverage', 'coffee', 'supply', 'sparepart', 'ramen',
    ];

    /** @return array<string, mixed> */
    public function build(): array
    {
        $now = Carbon::now();
        $days30 = $now->copy()->subDays(30);
        $days60 = $now->copy()->subDays(60);

        $summary = $this->summary($days30);
        $inventoryHealth = $this->inventoryHealth();
        $productPerformance = $this->productPerformance($days30, $days60);
        $pricing = $this->pricingInsights();
        $orderTrends = $this->orderTrends($days30, $now);
        $categoryAnalytics = $this->categoryAnalytics($days30);
        $inventoryValue = $this->inventoryValueAnalysis();

        return [
            'summary' => $summary,
            'inventoryHealth' => $inventoryHealth,
            'productPerformance' => $productPerformance,
            'pricing' => $pricing,
            'orderTrends' => $orderTrends,
            'categoryAnalytics' => $categoryAnalytics,
            'inventoryValue' => $inventoryValue,
            'recentStockMovements' => $this->recentStockMovements(),
            'charts' => [
                'inventoryByCategory' => $inventoryHealth['byCategoryChart'],
                'categorySales' => $productPerformance['categorySalesChart'],
                'dailyOrders' => $orderTrends['dailyChart'],
                'softserveVsOther' => $productPerformance['softserveChart'],
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function summary(Carbon $days30): array
    {
        $inventoryValue = (float) DB::table('product_inventory')
            ->join('product_prices', function ($join) {
                $join->on('product_inventory.product_id', '=', 'product_prices.product_id')
                    ->where('product_prices.region', '=', 'luzon');
            })
            ->selectRaw('COALESCE(SUM(product_inventory.stock * product_prices.price), 0) as total')
            ->value('total');

        return [
            'active_products' => Product::query()->where('status', 'active')->count(),
            'softserve_products' => Product::query()->where('category', ProductCategory::Softserve)->count(),
            'supply_sparepart_products' => Product::query()
                ->whereIn('category', [ProductCategory::Supply, ProductCategory::Sparepart])
                ->count(),
            'inventory_value_luzon' => $inventoryValue,
            'orders_last_30_days' => Order::query()
                ->where('created_at', '>=', $days30)
                ->count(),
        ];
    }

    /** @return array<string, mixed> */
    private function inventoryHealth(): array
    {
        $lowStock = $this->inventoryRows('product_inventory.stock > 0 AND product_inventory.stock < 10');
        $outOfStock = $this->inventoryRows('product_inventory.stock = 0');
        $overStocked = $this->inventoryRows('product_inventory.stock > 500');

        $byCategory = DB::table('products')
            ->leftJoin('product_inventory', 'products.id', '=', 'product_inventory.product_id')
            ->select('products.category', DB::raw('COALESCE(SUM(product_inventory.stock), 0) as total_stock'))
            ->groupBy('products.category')
            ->orderBy('products.category')
            ->get();

        $labels = [];
        $values = [];
        foreach (self::CATEGORY_ORDER as $cat) {
            $row = $byCategory->firstWhere('category', $cat);
            $labels[] = ProductCategory::tryFrom($cat)?->label() ?? ucfirst($cat);
            $values[] = (int) ($row->total_stock ?? 0);
        }

        return [
            'lowStock' => $lowStock,
            'outOfStock' => $outOfStock,
            'overStocked' => $overStocked,
            'byCategoryChart' => ['labels' => $labels, 'values' => $values],
        ];
    }

    /** @return array<string, mixed> */
    private function productPerformance(Carbon $days30, Carbon $days60): array
    {
        $salesBase = OrderItem::query()
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->where('orders.status', OrderStatus::Approved)
            ->where('orders.created_at', '>=', $days30);

        $bestSellers = (clone $salesBase)
            ->select(
                'products.id',
                'products.name',
                'products.category',
                DB::raw('SUM(order_items.qty) as units_sold'),
                DB::raw('SUM(order_items.qty * order_items.price) as revenue'),
            )
            ->groupBy('products.id', 'products.name', 'products.category')
            ->orderByDesc('units_sold')
            ->limit(10)
            ->get();

        $soldProductIds = OrderItem::query()
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('orders.status', OrderStatus::Approved)
            ->where('orders.created_at', '>=', $days60)
            ->distinct()
            ->pluck('order_items.product_id');

        $slowQuery = Product::query()
            ->with('inventory')
            ->where('status', 'active');

        if ($soldProductIds->isNotEmpty()) {
            $slowQuery->whereNotIn('id', $soldProductIds);
        }

        $slowMoving = $slowQuery
            ->orderBy('name')
            ->limit(10)
            ->get()
            ->map(fn (Product $p) => [
                'id' => $p->id,
                'name' => $p->name,
                'category' => $p->category->label(),
                'stock' => $p->stockLevel(),
            ]);

        $categorySales = (clone $salesBase)
            ->select('products.category', DB::raw('SUM(order_items.qty) as units'))
            ->groupBy('products.category')
            ->get();

        $pieLabels = [];
        $pieValues = [];
        foreach ($categorySales as $row) {
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
            'bestSellers' => $bestSellers,
            'slowMoving' => $slowMoving,
            'categorySalesChart' => ['labels' => $pieLabels, 'values' => $pieValues],
            'softserveChart' => [
                'labels' => ['Softserve', 'Non-Softserve'],
                'values' => [$softserveUnits, $otherUnits],
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function pricingInsights(): array
    {
        $rows = DB::table('products')
            ->leftJoin('product_prices as pl', fn ($j) => $j->on('products.id', '=', 'pl.product_id')->where('pl.region', 'luzon'))
            ->leftJoin('product_prices as pd', fn ($j) => $j->on('products.id', '=', 'pd.product_id')->where('pd.region', 'davao'))
            ->leftJoin('product_prices as pt', fn ($j) => $j->on('products.id', '=', 'pt.product_id')->where('pt.region', 'tacloban'))
            ->where('products.status', 'active')
            ->select(
                'products.id',
                'products.name',
                'products.category',
                DB::raw('COALESCE(pl.price, 0) as luzon'),
                DB::raw('COALESCE(pd.price, 0) as davao'),
                DB::raw('COALESCE(pt.price, 0) as tacloban'),
            )
            ->orderBy('products.name')
            ->get()
            ->map(function ($row) {
                $luzon = (float) $row->luzon;
                $davaoGap = $luzon > 0 ? round((($row->davao - $luzon) / $luzon) * 100, 1) : 0;
                $taclobanGap = $luzon > 0 ? round((($row->tacloban - $luzon) / $luzon) * 100, 1) : 0;

                return [
                    'name' => $row->name,
                    'category' => ProductCategory::tryFrom($row->category)?->label() ?? $row->category,
                    'luzon' => $luzon,
                    'davao' => (float) $row->davao,
                    'tacloban' => (float) $row->tacloban,
                    'davao_gap_pct' => $davaoGap,
                    'tacloban_gap_pct' => $taclobanGap,
                    'davao_alert' => $luzon > 0 && $row->davao > $luzon * 1.1,
                    'tacloban_alert' => $luzon > 0 && $row->tacloban > $luzon * 1.1,
                ];
            });

        return [
            'comparison' => $rows,
            'alerts' => $rows->filter(fn ($r) => $r['davao_alert'] || $r['tacloban_alert'])->values(),
        ];
    }

    /** @return array<string, mixed> */
    private function orderTrends(Carbon $days30, Carbon $now): array
    {
        $dayExpr = $this->sqlDayExpression('orders.created_at');
        $monthExpr = $this->sqlMonthExpression('orders.created_at');

        $daily = Order::query()
            ->where('orders.created_at', '>=', $days30)
            ->selectRaw("{$dayExpr} as day, COUNT(*) as total")
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

        $monthly = Order::query()
            ->where('orders.created_at', '>=', $now->copy()->subMonths(12))
            ->selectRaw("{$monthExpr} as month, COUNT(*) as total")
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        $regionRows = Order::query()
            ->where('orders.created_at', '>=', $days30)
            ->select('price_region', DB::raw('COUNT(*) as total'))
            ->groupBy('price_region')
            ->get();

        $regionDist = ['luzon' => 0, 'davao' => 0, 'tacloban' => 0];
        foreach ($regionRows as $row) {
            $key = $row->price_region?->value ?? (string) $row->price_region;
            if (isset($regionDist[$key])) {
                $regionDist[$key] = (int) $row->total;
            }
        }

        return [
            'dailyChart' => ['labels' => $dailyLabels, 'values' => $dailyValues],
            'monthly' => $monthly,
            'regionDistribution' => $regionDist,
        ];
    }

    private function sqlDayExpression(string $column): string
    {
        return match (DB::connection()->getDriverName()) {
            'sqlite' => "date({$column})",
            'pgsql' => "CAST({$column} AS DATE)",
            default => "DATE({$column})",
        };
    }

    private function sqlMonthExpression(string $column): string
    {
        return match (DB::connection()->getDriverName()) {
            'sqlite' => "strftime('%Y-%m', {$column})",
            'pgsql' => "to_char({$column}, 'YYYY-MM')",
            default => "DATE_FORMAT({$column}, '%Y-%m')",
        };
    }

    /** @return list<array<string, mixed>> */
    private function categoryAnalytics(Carbon $days30): array
    {
        $result = [];

        foreach (self::CATEGORY_ORDER as $category) {
            $products = Product::query()->where('category', $category);
            $productIds = (clone $products)->pluck('id');

            $totalStock = (int) DB::table('product_inventory')
                ->whereIn('product_id', $productIds)
                ->sum('stock');

            $avgLuzon = (float) DB::table('product_prices')
                ->whereIn('product_id', $productIds)
                ->where('region', 'luzon')
                ->avg('price') ?? 0;

            $salesQuery = OrderItem::query()
                ->join('orders', 'order_items.order_id', '=', 'orders.id')
                ->where('orders.status', OrderStatus::Approved)
                ->where('orders.created_at', '>=', $days30)
                ->whereIn('order_items.product_id', $productIds);

            $totalSales = (int) (clone $salesQuery)->sum('order_items.qty');

            $fastest = (clone $salesQuery)
                ->select('order_items.product_id', DB::raw('SUM(order_items.qty) as units'))
                ->groupBy('order_items.product_id')
                ->orderByDesc('units')
                ->first();

            $fastestName = $fastest
                ? Product::query()->find($fastest->product_id)?->name
                : '—';

            $slowestWithStock = Product::query()
                ->with('inventory')
                ->where('category', $category)
                ->where('status', 'active')
                ->get()
                ->sortBy('name')
                ->first();

            $result[] = [
                'category' => $category,
                'label' => ProductCategory::tryFrom($category)?->label() ?? ucfirst($category),
                'total_products' => $products->count(),
                'total_stock' => $totalStock,
                'total_sales_30d' => $totalSales,
                'avg_price_luzon' => round($avgLuzon, 2),
                'fastest_moving' => $fastestName ?? '—',
                'slowest_moving' => $slowestWithStock?->name ?? '—',
            ];
        }

        return $result;
    }

    /** @return array<string, mixed> */
    private function inventoryValueAnalysis(): array
    {
        $valueExpr = 'product_inventory.stock * product_prices.price';

        $byCategory = DB::table('products')
            ->join('product_inventory', 'products.id', '=', 'product_inventory.product_id')
            ->join('product_prices', function ($join) {
                $join->on('products.id', '=', 'product_prices.product_id')
                    ->where('product_prices.region', 'luzon');
            })
            ->select('products.category', DB::raw("SUM({$valueExpr}) as value"))
            ->groupBy('products.category')
            ->get();

        $byRegion = collect(['luzon', 'davao', 'tacloban'])->map(function ($region) use ($valueExpr) {
            $value = (float) DB::table('product_inventory')
                ->join('product_prices', function ($join) use ($region) {
                    $join->on('product_inventory.product_id', '=', 'product_prices.product_id')
                        ->where('product_prices.region', $region);
                })
                ->selectRaw("COALESCE(SUM({$valueExpr}), 0) as total")
                ->value('total');

            return ['region' => ucfirst($region), 'value' => $value];
        });

        $itemValues = DB::table('products')
            ->join('product_inventory', 'products.id', '=', 'product_inventory.product_id')
            ->join('product_prices', function ($join) {
                $join->on('products.id', '=', 'product_prices.product_id')
                    ->where('product_prices.region', 'luzon');
            })
            ->select(
                'products.name',
                'products.category',
                'product_inventory.stock',
                DB::raw("({$valueExpr}) as item_value"),
            )
            ->whereRaw("({$valueExpr}) > 0")
            ->orderByDesc('item_value')
            ->get();

        return [
            'byCategory' => $byCategory,
            'byRegion' => $byRegion,
            'highest' => $itemValues->take(10),
            'lowest' => $itemValues->sortBy('item_value')->take(10)->values(),
        ];
    }

    /** @return \Illuminate\Support\Collection<int, StockMovement> */
    private function recentStockMovements(): \Illuminate\Support\Collection
    {
        return StockMovement::query()
            ->with(['product', 'user'])
            ->latest('created_at')
            ->limit(10)
            ->get();
    }

    /** @return Collection<int, object> */
    private function inventoryRows(string $condition): Collection
    {
        return DB::table('products')
            ->join('product_inventory', 'products.id', '=', 'product_inventory.product_id')
            ->whereRaw($condition)
            ->select('products.name', 'products.category', 'product_inventory.stock')
            ->orderBy('product_inventory.stock')
            ->limit(50)
            ->get()
            ->map(fn ($row) => [
                'name' => $row->name,
                'category' => ProductCategory::tryFrom($row->category)?->label() ?? $row->category,
                'stock' => (int) $row->stock,
            ]);
    }
}
