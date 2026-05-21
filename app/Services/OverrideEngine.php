<?php

namespace App\Services;

use App\Enums\PointLedgerType;
use App\Enums\UserRole;
use App\Models\Order;
use App\Models\PointsLedger;
use App\Support\FrostySettings;

final class OverrideEngine
{
    public function __construct(
        private GenealogyEngine $genealogy,
        private QualificationEngine $qualification,
        private WalletService $wallets,
    ) {}

    /**
     * @return list<PointsLedger>
     */
    public function distributeForOrder(Order $order): array
    {
        $buyer = $order->user;

        if (! $buyer->isOperator() || $order->total_points <= 0) {
            return [];
        }

        $month = $order->approved_at?->format('Y-m') ?? FrostySettings::currentMonth();
        $entries = [];
        $chain = $this->genealogy->uplineChain($buyer);

        foreach ($chain as $level => $upline) {
            if (! $upline || $upline->role !== UserRole::Operator) {
                continue;
            }

            if (! $this->qualification->isQualified($upline, $month)) {
                continue;
            }

            $percent = FrostySettings::overridePercent($level);
            if ($percent <= 0) {
                continue;
            }

            if ($this->ledgerExists($order->id, $upline->id, PointLedgerType::Override, $level)) {
                continue;
            }

            $pesos = round(
                $order->total_points * FrostySettings::pesoPerPoint() * ($percent / 100),
                2,
            );

            if ($pesos <= 0) {
                continue;
            }

            $entry = PointsLedger::query()->create([
                'user_id' => $upline->id,
                'source_user_id' => $buyer->id,
                'level' => $level,
                'points' => $order->total_points,
                'pesos' => $pesos,
                'type' => PointLedgerType::Override,
                'month' => $month,
                'order_id' => $order->id,
            ]);

            $this->wallets->credit($upline, $pesos, 'override', $order->id);
            $entries[] = $entry;
        }

        return $entries;
    }

    private function ledgerExists(int $orderId, int $userId, PointLedgerType $type, int $level): bool
    {
        return PointsLedger::query()
            ->where('order_id', $orderId)
            ->where('user_id', $userId)
            ->where('type', $type)
            ->where('level', $level)
            ->exists();
    }
}
