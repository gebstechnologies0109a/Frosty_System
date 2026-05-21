<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\ProductCategory;
use App\Enums\UserRole;
use App\Models\Distributor;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class OrderAnalyticsService
{
    /** @var list<string> */
    private const CATEGORY_ORDER = [
        'softserve', 'yogurt', 'syrup', 'cone', 'beverage', 'coffee', 'supply', 'sparepart', 'ramen',
    ];

    /** @return array<string, mixed> */
    public function build(Request $request): array
    {
        $now = Carbon::now();
        $days30 = $now->copy()->subDays(30);

        $filtered = OrderAnalyticsFilter::baseQuery($request);
        $scoped = $this->scopedWithoutDates($request);
        $last30 = (clone $scoped)->where('created_at', '>=', $days30);

        $summary = $this->summary($scoped, $last30, $filtered, $request, $days30);
        $regionalStats = $this->regionalStats($filtered);
        $dailyTrends = $this->dailyTrends($filtered, $days30, $now);
        $monthlyTrends = $this->monthlyTrends($filtered, $now);
        $distributorStats = $this->distributorStats($filtered);
        $operatorStats = $this->operatorStats($filtered, $scoped, $days30);
        $productStats = $this->productStats($filtered);
        $categoryStats = $this->categoryStats($filtered);
        $statusStats = $this->statusStats($filtered);

        return [
            'summary' => $summary,
            'regionalStats' => $regionalStats,
            'dailyTrends' => $dailyTrends,
            'monthlyTrends' => $monthlyTrends,
            'distributorStats' => $distributorStats,
            'operatorStats' => $operatorStats,
            'productStats' => $productStats,
            'categoryStats' => $categoryStats,
            'statusStats' => $statusStats,
            'charts' => [
                'dailyCount' => $dailyTrends['countChart'],
                'dailyValue' => $dailyTrends['valueChart'],
                'regionComparison' => $regionalStats['chart'],
                'categoryPie' => $categoryStats['pieChart'],
                'softserveBar' => $categoryStats['softserveChart'],
                'monthlyCount' => [
                    'labels' => $monthlyTrends['labels'],
                    'values' => $monthlyTrends['counts'],
                ],
                'monthlyValue' => [
                    'labels' => $monthlyTrends['labels'],
                    'values' => $monthlyTrends['values'],
                ],
            ],
        ];
    }

    private function scopedWithoutDates(Request $request): Builder
    {
        $query = Order::query();
        if ($request->filled('region')) {
            $query->where('price_region', $request->input('region'));
        }
        if ($request->filled('distributor_id')) {
            $query->where('distributor_id', $request->input('distributor_id'));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        return $query;
    }

    /** @return array<string, mixed> */
    private function summary(Builder $scoped, Builder $last30, Builder $filtered, Request $request, Carbon $days30): array
    {
        $count30 = (clone $last30)->count();
        $value30 = (float) (clone $last30)->sum('total_amount');

        $softserveValue30 = (float) $this->orderItemsQuery($last30)
            ->where('products.category', ProductCategory::Softserve)
            ->selectRaw('COALESCE(SUM(order_items.qty * order_items.price), 0) as total')
            ->value('total');

        return [
            'total_orders_all_time' => (clone $scoped)->count(),
            'total_orders_30_days' => $count30,
            'total_value_30_days' => $value30,
            'avg_order_value_30_days' => $count30 > 0 ? round($value30 / $count30, 2) : 0,
            'softserve_value_30_days' => $softserveValue30,
            'filtered_count' => (clone $filtered)->count(),
            'filtered_value' => (float) (clone $filtered)->sum('total_amount'),
            'has_date_filter' => $request->filled('date_from') || $request->filled('date_to'),
        ];
    }

    /** @return array<string, mixed> */
    private function regionalStats(Builder $filtered): array
    {
        $rows = (clone $filtered)
            ->select(
                'price_region',
                DB::raw('COUNT(*) as order_count'),
                DB::raw('SUM(total_amount) as order_value'),
            )
            ->groupBy('price_region')
            ->get()
            ->keyBy(fn ($row) => $row->price_region?->value ?? (string) $row->price_region);

        $labels = [];
        $countValues = [];
        $valueValues = [];
        $table = [];

        foreach (['luzon', 'davao', 'tacloban'] as $region) {
            $row = $rows->get($region);
            $count = (int) ($row->order_count ?? 0);
            $value = (float) ($row->order_value ?? 0);
            $labels[] = ucfirst($region);
            $countValues[] = $count;
            $valueValues[] = round($value, 2);
            $table[] = [
                'region' => ucfirst($region),
                'orders' => $count,
                'value' => $value,
                'avg' => $count > 0 ? round($value / $count, 2) : 0,
            ];
        }

        return [
            'table' => $table,
            'chart' => [
                'labels' => $labels,
                'orderCounts' => $countValues,
                'orderValues' => $valueValues,
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function dailyTrends(Builder $filtered, Carbon $days30, Carbon $now): array
    {
        $daily = (clone $filtered)
            ->where('created_at', '>=', $days30)
            ->selectRaw('DATE(orders.created_at) as day, COUNT(*) as cnt, SUM(orders.total_amount) as val')
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->keyBy('day');

        $labels = [];
        $counts = [];
        $values = [];

        for ($d = $days30->copy(); $d->lte($now); $d->addDay()) {
            $key = $d->format('Y-m-d');
            $row = $daily->get($key);
            $labels[] = $d->format('M j');
            $counts[] = (int) ($row->cnt ?? 0);
            $values[] = round((float) ($row->val ?? 0), 2);
        }

        return [
            'countChart' => ['labels' => $labels, 'values' => $counts],
            'valueChart' => ['labels' => $labels, 'values' => $values],
        ];
    }

    /** @return array<string, mixed> */
    private function monthlyTrends(Builder $filtered, Carbon $now): array
    {
        $monthly = (clone $filtered)
            ->where('created_at', '>=', $now->copy()->subMonths(12))
            ->selectRaw("DATE_FORMAT(orders.created_at, '%Y-%m') as month, COUNT(*) as cnt, SUM(orders.total_amount) as val")
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        return [
            'labels' => $monthly->pluck('month')->all(),
            'counts' => $monthly->pluck('cnt')->map(fn ($v) => (int) $v)->all(),
            'values' => $monthly->pluck('val')->map(fn ($v) => round((float) $v, 2))->all(),
        ];
    }

    /** @return array<string, mixed> */
    private function distributorStats(Builder $filtered): array
    {
        $rows = (clone $filtered)
            ->join('distributors', 'orders.distributor_id', '=', 'distributors.id')
            ->leftJoin('users', 'distributors.user_id', '=', 'users.id')
            ->select(
                'distributors.name',
                'users.region',
                DB::raw('COUNT(orders.id) as order_count'),
                DB::raw('SUM(orders.total_amount) as order_value'),
            )
            ->groupBy('distributors.id', 'distributors.name', 'users.region')
            ->orderByDesc('order_value')
            ->limit(10)
            ->get()
            ->map(fn ($row) => [
                'name' => $row->name,
                'region' => $row->region ? ucfirst((string) $row->region) : '—',
                'orders' => (int) $row->order_count,
                'value' => (float) $row->order_value,
            ]);

        return ['top' => $rows, 'table' => $rows];
    }

    /** @return array<string, mixed> */
    private function operatorStats(Builder $filtered, Builder $scoped, Carbon $days30): array
    {
        $top = (clone $filtered)
            ->join('users', 'orders.user_id', '=', 'users.id')
            ->where('users.role', UserRole::Operator)
            ->select(
                'users.name',
                DB::raw('COUNT(orders.id) as order_count'),
                DB::raw('SUM(orders.total_amount) as order_value'),
            )
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('order_value')
            ->limit(20)
            ->get();

        $activeIds = (clone $scoped)
            ->where('created_at', '>=', $days30)
            ->join('users', 'orders.user_id', '=', 'users.id')
            ->where('users.role', UserRole::Operator)
            ->distinct()
            ->pluck('users.id');

        $inactive = User::query()
            ->where('role', UserRole::Operator)
            ->when($activeIds->isNotEmpty(), fn ($q) => $q->whereNotIn('id', $activeIds))
            ->orderBy('name')
            ->limit(20)
            ->get(['name']);

        $operatorCount = User::query()->where('role', UserRole::Operator)->count();
        $orders30 = (clone $scoped)
            ->where('created_at', '>=', $days30)
            ->whereHas('user', fn ($q) => $q->where('role', UserRole::Operator))
            ->count();

        return [
            'top' => $top,
            'inactive' => $inactive,
            'avg_orders_per_operator_30d' => $operatorCount > 0 ? round($orders30 / $operatorCount, 2) : 0,
        ];
    }

    /** @return array<string, mixed> */
    private function productStats(Builder $filtered): array
    {
        $base = $this->orderItemsQuery($filtered);

        $byQty = (clone $base)
            ->select('products.name', DB::raw('SUM(order_items.qty) as units'))
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('units')
            ->limit(10)
            ->get();

        $byValue = (clone $base)
            ->select('products.name', DB::raw('SUM(order_items.qty * order_items.price) as revenue'))
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('revenue')
            ->limit(10)
            ->get();

        return ['byQty' => $byQty, 'byValue' => $byValue];
    }

    /** @return array<string, mixed> */
    private function categoryStats(Builder $filtered): array
    {
        $rows = $this->orderItemsQuery($filtered)
            ->select(
                'products.category',
                DB::raw('SUM(order_items.qty) as units'),
                DB::raw('SUM(order_items.qty * order_items.price) as revenue'),
            )
            ->groupBy('products.category')
            ->get()
            ->keyBy('category');

        $breakdown = [];
        $pieLabels = [];
        $pieValues = [];

        foreach (self::CATEGORY_ORDER as $cat) {
            $row = $rows->get($cat);
            if (! $row || ((int) $row->units) === 0) {
                continue;
            }
            $label = ProductCategory::tryFrom($cat)?->label() ?? ucfirst($cat);
            $pieLabels[] = $label;
            $pieValues[] = (int) $row->units;
            $breakdown[] = [
                'category' => $label,
                'units' => (int) $row->units,
                'revenue' => (float) $row->revenue,
            ];
        }

        $softserveValue = (float) ($rows->get(ProductCategory::Softserve->value)->revenue ?? 0);
        $otherValue = $rows
            ->filter(fn ($row, $cat) => $cat !== ProductCategory::Softserve->value)
            ->sum('revenue');

        return [
            'breakdown' => $breakdown,
            'pieChart' => ['labels' => $pieLabels, 'values' => $pieValues],
            'softserveChart' => [
                'labels' => ['Softserve', 'Non-Softserve'],
                'values' => [round($softserveValue, 2), round((float) $otherValue, 2)],
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function statusStats(Builder $filtered): array
    {
        $total = (clone $filtered)->count();
        $counts = [
            'pending' => (clone $filtered)->where('status', OrderStatus::Pending)->count(),
            'approved' => (clone $filtered)->where('status', OrderStatus::Approved)->count(),
            'rejected' => (clone $filtered)->where('status', OrderStatus::Rejected)->count(),
        ];

        $resolved = $counts['approved'] + $counts['rejected'];
        $conversionRate = $counts['pending'] + $counts['approved'] > 0
            ? round(($counts['approved'] / ($counts['pending'] + $counts['approved'])) * 100, 1)
            : 0;
        $rejectionRate = $total > 0 ? round(($counts['rejected'] / $total) * 100, 1) : 0;

        return [
            'counts' => $counts,
            'total' => $total,
            'conversion_rate' => $conversionRate,
            'rejection_rate' => $rejectionRate,
            'note' => 'Approved orders represent fulfilled orders (no separate completed status).',
        ];
    }

    private function orderItemsQuery(Builder $orders): \Illuminate\Database\Query\Builder
    {
        return DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->whereIn('orders.id', (clone $orders)->select('orders.id'));
    }
}
