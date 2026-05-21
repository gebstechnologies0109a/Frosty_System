<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\ActivityLog;
use App\Models\Distributor;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

final class OperatorOrderService
{
    public function __construct(
        private ActivityLogger $logger,
    ) {}

    public function authorizeOwner(Order $order, User $operator): void
    {
        if ((int) $order->user_id !== (int) $operator->id) {
            abort(403);
        }
    }

    /**
     * @return list<array{label: string, at: ?\Illuminate\Support\Carbon, detail: ?string}>
     */
    public function statusTimeline(Order $order): array
    {
        $events = [
            [
                'label' => 'Submitted',
                'at' => $order->created_at,
                'detail' => 'Order placed',
            ],
        ];

        if ($order->status === OrderStatus::Rejected) {
            $events[] = [
                'label' => 'Rejected',
                'at' => $order->updated_at,
                'detail' => $order->approver?->name ? 'By '.$order->approver->name : null,
            ];
        } elseif ($order->approved_at) {
            $events[] = [
                'label' => 'Approved',
                'at' => $order->approved_at,
                'detail' => $order->approver?->name ? 'By '.$order->approver->name : null,
            ];
        }

        if ($order->completed_at) {
            $events[] = [
                'label' => 'Completed',
                'at' => $order->completed_at,
                'detail' => null,
            ];
        }

        return $events;
    }

    /**
     * @return \Illuminate\Support\Collection<int, ActivityLog>
     */
    public function orderActivity(Order $order)
    {
        return ActivityLog::query()
            ->where(function ($query) use ($order) {
                $query->where('meta->order_id', $order->id);
            })
            ->with('user')
            ->latest()
            ->get();
    }

    /**
     * @param  array<int, array{product_id: int, qty: int}>  $items
     */
    public function saveEditableOrder(
        Order $order,
        User $operator,
        array $items,
        int $distributorId,
        ?string $paymentProofPath = null,
        ?string $notes = null,
        bool $resubmitIfRejected = false,
    ): Order {
        $this->authorizeOwner($order, $operator);

        if (! in_array($order->status, [OrderStatus::Pending, OrderStatus::Rejected], true)) {
            throw new RuntimeException('This order can no longer be edited.');
        }

        $distributor = Distributor::query()->findOrFail($distributorId);
        $priceRegion = $distributor->operatorPriceRegion();
        $wasRejected = $order->status === OrderStatus::Rejected;

        return DB::transaction(function () use ($order, $operator, $items, $distributor, $priceRegion, $paymentProofPath, $notes, $resubmitIfRejected, $wasRejected) {
            $order->items()->delete();

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
                    'line_total' => $lineAmount,
                ]);

                $totalAmount += $lineAmount;
                $totalPoints += $linePoints;
            }

            $updates = [
                'operator_id' => $operator->id,
                'distributor_id' => $distributor->id,
                'price_region' => $priceRegion,
                'total_amount' => $totalAmount,
                'total_points' => $totalPoints,
                'status' => OrderStatus::Pending,
                'approved_by' => null,
                'approved_at' => null,
            ];

            if ($notes !== null) {
                $updates['notes'] = $notes;
            }

            if ($paymentProofPath !== null) {
                $this->deleteProofFile($order->payment_proof_path);
                $updates['payment_proof_path'] = $paymentProofPath;
            }

            $order->update($updates);

            $this->logger->log($operator, 'order.updated', [
                'order_id' => $order->id,
                'distributor_id' => $distributor->id,
            ]);

            if ($wasRejected) {
                $this->notifyDistributorOfResubmit($order->fresh(['distributor.user']), $operator);
            }

            return $order->fresh(['items.product', 'distributor', 'approver']);
        });
    }

    /** @deprecated Use saveEditableOrder() */
    public function updatePending(
        Order $order,
        User $operator,
        array $items,
        int $distributorId,
        ?string $paymentProofPath = null,
        ?string $notes = null,
    ): Order {
        return $this->saveEditableOrder($order, $operator, $items, $distributorId, $paymentProofPath, $notes);
    }

    public function uploadPaymentProof(Order $order, User $operator, string $path): Order
    {
        $this->authorizeOwner($order, $operator);

        if (! in_array($order->status, [OrderStatus::Pending, OrderStatus::Rejected], true)) {
            throw new RuntimeException('Payment proof can only be uploaded for pending or rejected orders.');
        }

        $this->deleteProofFile($order->payment_proof_path);
        $order->update(['payment_proof_path' => $path]);

        $this->logger->log($operator, 'order.payment_proof_uploaded', [
            'order_id' => $order->id,
        ]);

        return $order->fresh(['items.product', 'distributor']);
    }

    public function resubmit(Order $order, User $operator): Order
    {
        $this->authorizeOwner($order, $operator);

        if ($order->status !== OrderStatus::Rejected) {
            throw new RuntimeException('Only rejected orders can be re-submitted.');
        }

        $order->update([
            'status' => OrderStatus::Pending,
            'approved_by' => null,
            'approved_at' => null,
        ]);

        $this->logger->log($operator, 'order.resubmitted', [
            'order_id' => $order->id,
            'distributor_id' => $order->distributor_id,
        ]);

        $this->notifyDistributorOfResubmit($order->fresh(['distributor.user']), $operator);

        return $order->fresh(['items.product', 'distributor']);
    }

    private function notifyDistributorOfResubmit(Order $order, User $operator): void
    {
        $distributorUser = $order->distributor?->user;
        if ($distributorUser) {
            $this->logger->log($distributorUser, 'order.resubmitted_notify', [
                'order_id' => $order->id,
                'operator_id' => $operator->id,
                'operator_name' => $operator->name,
            ]);
        }
    }

    private function deleteProofFile(?string $path): void
    {
        if ($path && Storage::disk(OrderPaymentProofService::DISK)->exists($path)) {
            Storage::disk(OrderPaymentProofService::DISK)->delete($path);
        }
    }
}
