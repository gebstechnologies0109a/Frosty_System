<?php

namespace Database\Seeders;

use App\Models\Member;
use App\Models\Store;
use App\Services\FrostyRewardEngine;
use Illuminate\Database\Seeder;

class FrostySeeder extends Seeder
{
    public function run(FrostyRewardEngine $engine): void
    {
        $store = Store::query()->firstOrCreate(
            ['code' => 'FROSTY-01'],
            ['name' => 'Frosty Main Store', 'is_active' => true],
        );

        $root = Member::query()->firstOrCreate(
            ['member_code' => 'FROOT'],
            ['name' => 'Frosty Root', 'email' => 'root@frosty.local'],
        );

        $sponsor = Member::query()->firstOrCreate(
            ['member_code' => 'FSP01'],
            [
                'name' => 'Ana Sponsor',
                'email' => 'ana@frosty.local',
                'referrer_member_id' => $root->id,
            ],
        );

        $buyer = Member::query()->firstOrCreate(
            ['member_code' => 'FBUY01'],
            [
                'name' => 'Ben Buyer',
                'email' => 'ben@frosty.local',
                'referrer_member_id' => $sponsor->id,
            ],
        );

        $engine->recordPurchase($store, $sponsor, 25.0, notes: 'Seed: sponsor qualifies for override');
        $engine->recordPurchase($store, $buyer, 10.0, notes: 'Seed: buyer purchase triggers override');
    }
}
