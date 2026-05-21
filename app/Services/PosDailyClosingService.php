<?php

namespace App\Services;

use App\Enums\OrderType;
use App\Enums\PosDailyClosingStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PosDailyClosing;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

final class PosDailyClosingService
{
    public function todayForOperator(User $operator): ?PosDailyClosing
    {
        return PosDailyClosing::query()
            ->where('operator_id', $operator->id)
            ->whereDate('closing_date', Carbon::today())
            ->first();
    }

    public function isTodayLocked(User $operator): bool
    {
        $closing = $this->todayForOperator($operator);

        return $closing !== null && $closing->locksPosDay();
    }

    /** @return array<string, mixed> */
    public function todayTotals(User $operator, ?Carbon $date = null): array
    {
        $date = $date ?? Carbon::today();

        $orders = Order::query()
            ->pos()
            ->where('operator_id', $operator->id)
            ->whereDate('created_at', $date);

        $totalSales = (float) (clone $orders)->sum('total_amount');
        $totalCogs = (float) (clone $orders)->sum('cogs_total');
        $grossProfit = (float) (clone $orders)->sum('gross_profit');

        if ($totalSales <= 0) {
            $lineStats = $this->totalsFromLineItems($operator, $date);
            $totalSales = $lineStats['total_sales'];
            $totalCogs = $lineStats['total_cogs'];
            $grossProfit = round($totalSales - $totalCogs, 2);
        }

        $margin = $totalSales > 0 ? round(($grossProfit / $totalSales) * 100, 2) : 0;

        return [
            'closing_date' => $date->toDateString(),
            'total_sales' => $totalSales,
            'total_cogs' => $totalCogs,
            'gross_profit' => $grossProfit,
            'gross_margin_percent' => $margin,
            'expected_cash' => $totalSales,
            'order_count' => (int) (clone $orders)->count(),
        ];
    }

    public function submit(User $operator, float $actualCash, ?string $notes = null): PosDailyClosing
    {
        if ($this->todayForOperator($operator)) {
            throw ValidationException::withMessages([
                'closing' => 'Daily closing has already been submitted for today.',
            ]);
        }

        $totals = $this->todayTotals($operator);
        $variance = round($actualCash - $totals['expected_cash'], 2);

        return PosDailyClosing::query()->create([
            'operator_id' => $operator->id,
            'closing_date' => Carbon::today(),
            'total_sales' => $totals['total_sales'],
            'total_cogs' => $totals['total_cogs'],
            'gross_profit' => $totals['gross_profit'],
            'gross_margin_percent' => $totals['gross_margin_percent'],
            'expected_cash' => $totals['expected_cash'],
            'actual_cash' => $actualCash,
            'variance' => $variance,
            'notes' => $notes,
            'status' => PosDailyClosingStatus::Pending,
        ]);
    }

    public function approve(PosDailyClosing $closing, User $admin): PosDailyClosing
    {
        $closing->update([
            'status' => PosDailyClosingStatus::Approved,
            'approved_by' => $admin->id,
        ]);

        return $closing->fresh(['operator', 'approver']);
    }

    public function reject(PosDailyClosing $closing, User $admin): PosDailyClosing
    {
        $closing->update([
            'status' => PosDailyClosingStatus::Rejected,
            'approved_by' => $admin->id,
        ]);

        return $closing->fresh(['operator', 'approver']);
    }

    public function reopen(PosDailyClosing $closing): void
    {
        $closing->delete();
    }

    /** @return array{total_sales: float, total_cogs: float} */
    private function totalsFromLineItems(User $operator, Carbon $date): array
    {
        $rows = OrderItem::query()
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('operator_products', 'order_items.operator_product_id', '=', 'operator_products.id')
            ->where('orders.order_type', OrderType::Pos)
            ->where('orders.operator_id', $operator->id)
            ->whereDate('orders.created_at', $date)
            ->selectRaw('COALESCE(SUM(order_items.line_total), 0) as sales, COALESCE(SUM(order_items.qty * COALESCE(order_items.cost_price, operator_products.cost, 0)), 0) as cogs')
            ->first();

        return [
            'total_sales' => (float) ($rows->sales ?? 0),
            'total_cogs' => (float) ($rows->cogs ?? 0),
        ];
    }
}
