<?php

namespace App\Services;

use App\Models\Member;
use App\Support\FrostyRules;

final class FrostyOverrideChainResolver
{
    /**
     * @return array<int, Member|null> Keys 1–4 (L1 = buyer, L2–L4 = upline chain)
     */
    public function resolve(Member $buyer): array
    {
        $chain = [1 => $buyer];
        $referrerId = $buyer->referrer_member_id;

        for ($level = 2; $level <= FrostyRules::MAX_UPLINE_LEVELS; $level++) {
            if ($referrerId === null) {
                $chain[$level] = null;

                continue;
            }

            $upline = Member::query()->find($referrerId);
            $chain[$level] = $upline;
            $referrerId = $upline?->referrer_member_id;
        }

        return $chain;
    }
}
