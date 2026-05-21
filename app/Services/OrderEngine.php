<?php

namespace App\Services;

use App\Enums\OrderSource;
use App\Enums\OrderStatus;
use App\Enums\PointLedgerType;
use App\Enums\PriceRegion;
use App\Enums\UserRole;
use App\Models\Distributor;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PointsLedger;
use App\Models\Product;
use App\Models\User;
use App\Support\FrostySettings;
use Illuminate\Support\Facades\DB;

final class OrderEngine
{
    public function __construct(
        private QualificationEngine $qualification,
        private OverrideEngine $override,
        private WalletService $wallets,
        private ActivityLogger $logger,
    ) {}

    /**
     * @param  array<int, array{product_id: int, qty: int}>  $items
     */
    public function create(User $user, array $items, int $distributorId): Order
    {
        $distributor = Distributor::query()->findOrFail($distributorId);

        $source = $user->role === UserRole::Distributor
            ? OrderSource::Distributor
            : OrderSource::Operator;

        $priceRegion = $user->priceRegion();

        return DB::transaction(function () use ($user, $items, $distributorId, $source, $priceRegion) {
            $order = Order::query()->create([
                'user_id' => $user->id,
                'distributor_id' => $distributorId,
                'status' => OrderStatus::Pending,
                'total_amount' => 0,
                'total_points' => 0,
                'source' => $source,
                'price_region' => $priceRegion,
            ]);

            $totalAmount = 0;
            $totalPoints = 0;

            foreach ($items as $row) {
                $product = Product::query()->findOrFail($row['product_id']);
                $qty = max(1, (int) $row['qty']);
                $linePoints = ($product->category?->earnsRebatePoints() ?? false) ? $product->points * $qty : 0;
                $unitPrice = $product->priceForRegion($priceRegion);
                $lineAmount = $unitPrice * $qty;

                OrderItem::query()->create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'qty' => $qty,
                    'price' => $unitPrice,
                    'points' => $linePoints,
                ]);

                $totalAmount += $lineAmount;
                $totalPoints += $linePoints;
            }

            $order->update([
                'total_amount' => $totalAmount,
                'total_points' => $totalPoints,
            ]);

            $this->logger->log($user, 'order.created', [
                'order_id' => $order->id,
                'distributor_id' => $distributorId,
            ]);

            return $order->load(['items.product', 'distributor']);
        });
    }

    public function approve(Order $order, User $actor): Order
    {
        if ($order->status !== OrderStatus::Pending) {
            throw new \RuntimeException('Order is not pending.');
        }

        $this->assertCanApprove($order, $actor);

        return DB::transaction(function () use ($order, $actor) {
            $order->update([
                'status' => OrderStatus::Approved,
                'approved_by' => $actor->id,
                'approved_at' => now(),
            ]);

            $this->processRebates($order->fresh(['user']));

            $this->logger->log($actor, 'order.approved', ['order_id' => $order->id]);

            return $order->fresh(['items.product', 'user', 'distributor']);
        });
    }

    public function reject(Order $order, User $actor): Order
    {
        $this->assertCanApprove($order, $actor);

        $order->update(['status' => OrderStatus::Rejected]);
        $this->logger->log($actor, 'order.rejected', ['order_id' => $order->id]);

        return $order->fresh();
    }

    public function complete(Order $order, User $actor): Order
    {
        if ($order->status !== OrderStatus::Approved) {
            throw new \RuntimeException('Order must be approved first.');
        }

        $order->update(['status' => OrderStatus::Completed]);
        $this->logger->log($actor, 'order.completed', ['order_id' => $order->id]);

        return $order->fresh();
    }

    public function processRebates(Order $order): void
    {
        $buyer = $order->user;

        if (! $buyer->earnsRebates() || $order->total_points <= 0) {
            return;
        }

        $month = $order->approved_at?->format('Y-m') ?? FrostySettings::currentMonth();

        if (! PointsLedger::query()
            ->where('order_id', $order->id)
            ->where('user_id', $buyer->id)
            ->where('type', PointLedgerType::Self)
            ->where('level', 0)
            ->exists()) {
            $pesos = round($order->total_points * FrostySettings::pesoPerPoint(), 2);

            PointsLedger::query()->create([
                'user_id' => $buyer->id,
                'source_user_id' => $buyer->id,
                'level' => 0,
                'points' => $order->total_points,
                'pesos' => $pesos,
                'type' => PointLedgerType::Self,
                'month' => $month,
                'order_id' => $order->id,
            ]);

            $this->wallets->credit($buyer, $pesos, 'self_rebate', $order->id);
            $this->qualification->recordPersonalPoints($buyer, $order->total_points, $month);
        }

        $this->override->distributeForOrder($order);
    }

    private function assertCanApprove(Order $order, User $actor): void
    {
        if ($actor->role === UserRole::SuperAdmin || $actor->role === UserRole::PurchasingAdmin) {
            return;
        }

        if ($actor->role === UserRole::Distributor) {
            $profile = $actor->distributorProfile;
            if ($profile && (int) $order->distributor_id === (int) $profile->id && ! $order->isRoutedToMain()) {
                return;
            }
        }

        throw new \RuntimeException('You cannot approve this order.');
    }
}
