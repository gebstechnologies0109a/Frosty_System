<?php

namespace App\Services;

use App\Enums\OrderSource;
use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Enums\PaymentMethod;
use App\Models\Distributor;
use App\Models\OperatorProduct;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class OperatorPosService
{
    public function __construct(
        private OperatorProductDefaultsService $defaults,
        private PosDailyClosingService $dailyClosing,
    ) {}

    /** @return array<string, mixed> */
    public function posPageData(User $operator): array
    {
        $this->defaults->ensureDefaults($operator);

        $products = OperatorProduct::query()
            ->where('operator_id', $operator->id)
            ->where('status', 'active')
            ->orderBy('product_name')
            ->get()
            ->map(fn (OperatorProduct $op) => [
                'operator_product_id' => $op->id,
                'name' => $op->product_name,
                'description' => $op->description,
                'price' => (float) $op->price,
                'cost' => $op->unitCost(),
                'image_url' => $op->imageUrl(),
                'status' => $op->status,
            ])
            ->values()
            ->all();

        return [
            'products' => $products,
            'summary' => $this->salesSummary($operator),
            'pnl' => $this->profitAndLoss($operator),
        ];
    }

    /**
     * @param  array<int, array{operator_product_id: int, qty: int}>  $items
     */
    public function checkout(User $operator, array $items, PaymentMethod $paymentMethod): Order
    {
        if ($this->dailyClosing->isTodayLocked($operator)) {
            throw ValidationException::withMessages([
                'closing' => 'Daily closing is complete for today. No new sales can be recorded.',
            ]);
        }

        if ($items === []) {
            throw ValidationException::withMessages(['items' => 'Cart is empty.']);
        }

        $merged = [];
        foreach ($items as $row) {
            $id = (int) $row['operator_product_id'];
            $merged[$id] = ($merged[$id] ?? 0) + max(1, (int) $row['qty']);
        }

        $region = $operator->priceRegion();

        return DB::transaction(function () use ($operator, $merged, $region, $paymentMethod) {
            $order = Order::query()->create([
                'user_id' => $operator->id,
                'operator_id' => $operator->id,
                'distributor_id' => $operator->distributor_id ?? Distributor::mainId(),
                'status' => OrderStatus::Completed,
                'source' => OrderSource::Pos,
                'order_type' => OrderType::Pos,
                'payment_method' => $paymentMethod,
                'price_region' => $region,
                'total_amount' => 0,
                'total_points' => 0,
                'cogs_total' => 0,
                'gross_profit' => 0,
                'completed_at' => now(),
                'approved_at' => now(),
            ]);

            $revenue = 0;
            $cogs = 0;

            foreach ($merged as $operatorProductId => $qty) {
                $op = OperatorProduct::query()
                    ->where('operator_id', $operator->id)
                    ->where('id', $operatorProductId)
                    ->where('status', 'active')
                    ->firstOrFail();

                $unitPrice = (float) $op->price;
                $unitCost = $op->unitCost();
                $lineTotal = round($unitPrice * $qty, 2);
                $lineCogs = round($unitCost * $qty, 2);

                OrderItem::query()->create([
                    'order_id' => $order->id,
                    'operator_product_id' => $op->id,
                    'product_id' => null,
                    'qty' => $qty,
                    'price' => $unitPrice,
                    'cost_price' => $unitCost,
                    'line_total' => $lineTotal,
                    'points' => 0,
                ]);

                $revenue += $lineTotal;
                $cogs += $lineCogs;
            }

            $order->update([
                'total_amount' => $revenue,
                'cogs_total' => $cogs,
                'gross_profit' => round($revenue - $cogs, 2),
            ]);

            return $order->load('items.operatorProduct');
        });
    }

    /** @return array<string, mixed> */
    public function salesSummary(User $operator): array
    {
        $now = Carbon::now();
        $base = Order::query()->pos()->where('operator_id', $operator->id);

        return [
            'today' => (float) (clone $base)->whereDate('created_at', $now->toDateString())->sum('total_amount'),
            'week' => (float) (clone $base)->where('created_at', '>=', $now->copy()->startOfWeek())->sum('total_amount'),
            'month' => (float) (clone $base)->where('created_at', '>=', $now->copy()->startOfMonth())->sum('total_amount'),
        ];
    }

    /** @return array<string, mixed> */
    public function profitAndLoss(User $operator): array
    {
        $now = Carbon::now();
        $month = Order::query()->pos()->where('operator_id', $operator->id)
            ->where('created_at', '>=', $now->copy()->startOfMonth());

        $sales = (float) $month->sum('total_amount');
        $cogs = (float) $month->sum('cogs_total');
        $profit = (float) $month->sum('gross_profit');

        $topProducts = OrderItem::query()
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('operator_products', 'order_items.operator_product_id', '=', 'operator_products.id')
            ->where('orders.order_type', OrderType::Pos)
            ->where('orders.operator_id', $operator->id)
            ->where('orders.created_at', '>=', $now->copy()->startOfMonth())
            ->select('operator_products.product_name', DB::raw('SUM(order_items.qty) as units'))
            ->groupBy('operator_products.id', 'operator_products.product_name')
            ->orderByDesc('units')
            ->limit(5)
            ->get();

        return [
            'sales_month' => $sales,
            'cogs_month' => $cogs,
            'gross_profit_month' => $profit,
            'margin_month' => $sales > 0 ? round(($profit / $sales) * 100, 1) : 0,
            'top_products' => $topProducts,
        ];
    }
}
