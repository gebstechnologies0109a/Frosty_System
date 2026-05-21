<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\PointLedgerType;
use App\Enums\ProductCategory;
use App\Enums\UserRole;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PointsLedger;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

final class OperatorAnalyticsService
{
    private const SOFTSERVE_POINTS_PER_KILO = 2;

    public function __construct(
        private GenealogyEngine $genealogy,
    ) {}

    /** @return array<string, mixed> */
    public function build(User $operator): array
    {
        $now = Carbon::now();
        $days30 = $now->copy()->subDays(30);

        $downlines = $this->genealogy->downlinesByLevel($operator);
        $level1to4Report = $this->buildDownlineReport($operator, $downlines, $days30);
        $level0to4Report = $this->buildFullNetworkReport($operator, $downlines, $days30);

        $selfSummary = $this->selfSummary($operator, $days30);

        return [
            'summary' => $selfSummary,
            'level1to4Report' => $level1to4Report,
            'level0to4Report' => $level0to4Report,
            'rebatesEnabled' => $operator->earnsRebates(),
            'charts' => [
                'downlineOrders' => [
                    'labels' => collect($level1to4Report['rows'])->pluck('label')->all(),
                    'orders' => collect($level1to4Report['rows'])->pluck('orders')->all(),
                    'points' => collect($level1to4Report['rows'])->pluck('softserve_points')->all(),
                ],
                'fullNetworkOrders' => [
                    'labels' => collect($level0to4Report['rows'])->pluck('label')->all(),
                    'orders' => collect($level0to4Report['rows'])->pluck('orders')->all(),
                    'values' => collect($level0to4Report['rows'])->pluck('order_value')->all(),
                ],
                'selfVsDownline' => [
                    'labels' => ['Self (L0)', 'Downline (L1–L4)'],
                    'orders' => [
                        $level0to4Report['rows'][0]['orders'] ?? 0,
                        collect($level0to4Report['rows'])->slice(1)->sum('orders'),
                    ],
                    'values' => [
                        $level0to4Report['rows'][0]['order_value'] ?? 0,
                        collect($level0to4Report['rows'])->slice(1)->sum('order_value'),
                    ],
                ],
            ],
        ];
    }

    /**
     * @param  array<int, Collection<int, User>>  $downlines
     * @return array<string, mixed>
     */
    private function buildDownlineReport(User $operator, array $downlines, Carbon $days30): array
    {
        $rows = [];
        for ($level = 1; $level <= 4; $level++) {
            $rows[] = $this->levelMetrics($operator, $level, $downlines[$level] ?? collect(), $days30);
        }

        return [
            'rows' => $rows,
            'grand_total' => $this->grandTotal($rows),
        ];
    }

    /**
     * @param  array<int, Collection<int, User>>  $downlines
     * @return array<string, mixed>
     */
    private function buildFullNetworkReport(User $operator, array $downlines, Carbon $days30): array
    {
        $rows = [
            $this->levelMetrics($operator, 0, collect([$operator]), $days30, true),
        ];

        for ($level = 1; $level <= 4; $level++) {
            $rows[] = $this->levelMetrics($operator, $level, $downlines[$level] ?? collect(), $days30);
        }

        return [
            'rows' => $rows,
            'grand_total' => $this->grandTotal($rows),
        ];
    }

    /**
     * @param  Collection<int, User>  $operators
     * @return array<string, mixed>
     */
    private function levelMetrics(User $viewer, int $level, Collection $operators, Carbon $days30, bool $isSelf = false): array
    {
        $operatorIds = $operators->pluck('id');

        if ($operatorIds->isEmpty()) {
            return $this->emptyLevelRow($level, $isSelf);
        }

        $ordersQuery = Order::query()
            ->whereIn('user_id', $operatorIds)
            ->where('created_at', '>=', $days30);

        $orders = (int) (clone $ordersQuery)->count();
        $orderValue = (float) (clone $ordersQuery)->sum('total_amount');

        $softserveKilos = (float) OrderItem::query()
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->whereIn('orders.user_id', $operatorIds)
            ->where('orders.created_at', '>=', $days30)
            ->where('products.category', ProductCategory::Softserve)
            ->sum('order_items.qty');

        $softservePoints = (int) round($softserveKilos * self::SOFTSERVE_POINTS_PER_KILO);

        $nonSoftserveOrders = (int) Order::query()
            ->whereIn('user_id', $operatorIds)
            ->where('created_at', '>=', $days30)
            ->whereHas('items.product', fn ($q) => $q->where('category', '!=', ProductCategory::Softserve))
            ->count();

        $earnings = $viewer->earnsRebates()
            ? $this->earningsForLevel($viewer, $level, $operatorIds, $days30, $isSelf)
            : null;

        return [
            'level' => $level,
            'label' => $isSelf ? 'Level 0 (You)' : "Level {$level}",
            'operators' => $operatorIds->count(),
            'orders' => $orders,
            'order_value' => round($orderValue, 2),
            'softserve_points' => $softservePoints,
            'softserve_kilos' => round($softserveKilos, 2),
            'non_softserve_orders' => $nonSoftserveOrders,
            'earnings' => $earnings !== null ? round($earnings, 2) : null,
        ];
    }

    /** @return array<string, mixed> */
    private function emptyLevelRow(int $level, bool $isSelf): array
    {
        return [
            'level' => $level,
            'label' => $isSelf ? 'Level 0 (You)' : "Level {$level}",
            'operators' => $isSelf ? 1 : 0,
            'orders' => 0,
            'order_value' => 0,
            'softserve_points' => 0,
            'softserve_kilos' => 0,
            'non_softserve_orders' => 0,
            'earnings' => 0,
        ];
    }

    /**
     * @param  Collection<int, int>  $operatorIds
     */
    private function earningsForLevel(User $viewer, int $level, Collection $operatorIds, Carbon $days30, bool $isSelf): float
    {
        if ($isSelf) {
            return (float) PointsLedger::query()
                ->where('user_id', $viewer->id)
                ->where('type', PointLedgerType::Self)
                ->where('level', 0)
                ->whereIn('source_user_id', $operatorIds)
                ->whereHas('order', fn ($q) => $q->where('created_at', '>=', $days30))
                ->sum('pesos');
        }

        if ($level < 1 || $level > 4) {
            return 0;
        }

        return (float) PointsLedger::query()
            ->where('user_id', $viewer->id)
            ->where('type', PointLedgerType::Override)
            ->where('level', $level)
            ->whereIn('source_user_id', $operatorIds)
            ->whereHas('order', fn ($q) => $q->where('created_at', '>=', $days30))
            ->sum('pesos');
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return array<string, mixed>
     */
    private function grandTotal(array $rows): array
    {
        $hasEarnings = collect($rows)->contains(fn ($r) => $r['earnings'] !== null);

        return [
            'label' => 'Grand Total',
            'operators' => array_sum(array_column($rows, 'operators')),
            'orders' => array_sum(array_column($rows, 'orders')),
            'order_value' => round(array_sum(array_column($rows, 'order_value')), 2),
            'softserve_points' => array_sum(array_column($rows, 'softserve_points')),
            'softserve_kilos' => round(array_sum(array_column($rows, 'softserve_kilos')), 2),
            'non_softserve_orders' => array_sum(array_column($rows, 'non_softserve_orders')),
            'earnings' => $hasEarnings ? round(array_sum(array_map(fn ($r) => (float) ($r['earnings'] ?? 0), $rows)), 2) : null,
        ];
    }

    /** @return array<string, mixed> */
    private function selfSummary(User $operator, Carbon $days30): array
    {
        $orders30 = Order::query()
            ->where('user_id', $operator->id)
            ->where('created_at', '>=', $days30);

        $downlineCount = User::query()
            ->where('role', UserRole::Operator)
            ->where('genealogy_path', 'like', '%/'.$operator->id.'/%')
            ->where('id', '!=', $operator->id)
            ->count();

        return [
            'orders_last_30_days' => (clone $orders30)->count(),
            'order_value_last_30_days' => (float) (clone $orders30)->sum('total_amount'),
            'downline_operators' => $downlineCount,
            'approved_orders' => (clone $orders30)->where('status', OrderStatus::Approved)->count(),
        ];
    }
}
