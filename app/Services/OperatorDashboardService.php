<?php

namespace App\Services;

use App\Enums\OrderType;
use App\Models\OperatorInventory;
use App\Models\Order;
use App\Models\StockMovement;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

final class OperatorDashboardService
{
    public function __construct(
        private readonly PosDailyClosingService $dailyClosing,
        private readonly OperatorInventoryService $inventory,
        private readonly OperatorPosService $pos,
    ) {}

    /** @return array<string, mixed> */
    public function metrics(User $operator): array
    {
        $today = Carbon::today();
        $since30 = now()->subDays(30)->startOfDay();

        $this->inventory->ensureSupplyRows($operator);

        $todayPos = $this->dailyClosing->todayTotals($operator);

        $orders30Query = Order::query()
            ->where('user_id', $operator->id)
            ->where(fn ($q) => $q->whereNull('order_type')->orWhere('order_type', '!=', OrderType::Pos))
            ->where('created_at', '>=', $since30);

        $orders30Count = (int) (clone $orders30Query)->count();
        $orders30Amount = (float) (clone $orders30Query)->sum('total_amount');

        $ordersTodayCount = (int) Order::query()
            ->where('user_id', $operator->id)
            ->where(fn ($q) => $q->whereNull('order_type')->orWhere('order_type', '!=', OrderType::Pos))
            ->whereDate('created_at', $today)
            ->count();

        $posSummary = $this->pos->salesSummary($operator);

        $inventoryRows = OperatorInventory::query()
            ->where('operator_id', $operator->id)
            ->with('product')
            ->get();

        $lowStock = $inventoryRows->filter(fn (OperatorInventory $r) => $r->stockStatus() === 'low_stock')->count();
        $outOfStock = $inventoryRows->filter(fn (OperatorInventory $r) => $r->stockStatus() === 'out_of_stock')->count();
        $alertCount = $lowStock + $outOfStock;

        $inventoryUsageToday = (int) StockMovement::query()
            ->where('user_id', $operator->id)
            ->whereDate('created_at', $today)
            ->where('quantity_change', '<', 0)
            ->sum(DB::raw('ABS(quantity_change)'));

        return [
            'cards' => [
                'orders_30' => [
                    'count' => $orders30Count,
                    'amount' => $orders30Amount,
                    'url' => route('operator.orders.index'),
                ],
                'pos_sales' => [
                    'today' => $posSummary['today'],
                    'month' => $posSummary['month'],
                    'url' => route('operator.pos.index'),
                ],
                'inventory_alerts' => [
                    'count' => $alertCount,
                    'low' => $lowStock,
                    'out' => $outOfStock,
                    'url' => route('operator.supplies-inventory.index', ['stock_status' => 'low_stock']),
                ],
            ],
            'today' => [
                'sales' => $todayPos['total_sales'],
                'orders' => $ordersTodayCount + (int) $todayPos['order_count'],
                'profit' => $todayPos['gross_profit'],
                'inventory_usage' => $inventoryUsageToday,
            ],
        ];
    }
}
