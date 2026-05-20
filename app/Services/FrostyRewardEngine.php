<?php

namespace App\Services;

use App\Models\KiloPurchase;
use App\Models\Member;
use App\Models\PointLedger;
use App\Models\Store;
use App\Support\FrostyRules;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

final class FrostyRewardEngine
{
    public function __construct(
        private FrostyOverrideChainResolver $chainResolver,
        private FrostyQualificationService $qualificationService,
    ) {}

    /**
     * @return array{purchase: KiloPurchase, ledger: list<PointLedger>}
     */
    public function recordPurchase(
        Store $store,
        Member $member,
        float $kilos,
        ?Carbon $purchasedAt = null,
        ?string $notes = null,
    ): array {
        $purchasedAt ??= now();
        $kilos = round(max(0.01, $kilos), 2);
        $directPoints = FrostyRules::directPoints($kilos);
        $year = (int) $purchasedAt->year;
        $month = (int) $purchasedAt->month;

        return DB::transaction(function () use (
            $store,
            $member,
            $kilos,
            $directPoints,
            $purchasedAt,
            $notes,
            $year,
            $month,
        ) {
            $purchase = KiloPurchase::query()->create([
                'store_id' => $store->id,
                'member_id' => $member->id,
                'kilos' => $kilos,
                'direct_points' => $directPoints,
                'purchased_at' => $purchasedAt,
                'notes' => $notes,
            ]);

            $ledger = [];
            $chain = $this->chainResolver->resolve($member);

            foreach ($chain as $level => $recipient) {
                if ($recipient === null) {
                    continue;
                }

                if ($level === 1) {
                    $ledger[] = $this->createLedgerEntry(
                        $recipient,
                        FrostyRules::TYPE_DIRECT,
                        $directPoints,
                        $kilos,
                        $member,
                        $purchase,
                        $year,
                        $month,
                    );

                    continue;
                }

                if (! $this->qualificationService->isQualifiedForOverride($recipient, $purchasedAt)) {
                    continue;
                }

                $overridePoints = FrostyRules::overridePoints($kilos);

                if ($overridePoints <= 0) {
                    continue;
                }

                $ledger[] = $this->createLedgerEntry(
                    $recipient,
                    FrostyRules::transactionTypeForLevel($level),
                    $overridePoints,
                    $kilos,
                    $member,
                    $purchase,
                    $year,
                    $month,
                );
            }

            foreach (array_filter($chain) as $chainMember) {
                $this->qualificationService->syncMonthlySummary($chainMember, $purchasedAt);
            }

            return [
                'purchase' => $purchase->load(['store', 'member']),
                'ledger' => $ledger,
            ];
        });
    }

    private function createLedgerEntry(
        Member $recipient,
        string $type,
        float $points,
        float $kilosBasis,
        Member $sourceMember,
        KiloPurchase $purchase,
        int $year,
        int $month,
    ): PointLedger {
        return PointLedger::query()->create([
            'member_id' => $recipient->id,
            'type' => $type,
            'points' => $points,
            'kilos_basis' => $kilosBasis,
            'source_member_id' => $sourceMember->id,
            'kilo_purchase_id' => $purchase->id,
            'period_year' => $year,
            'period_month' => $month,
        ]);
    }
}
