<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Collection;

final class GenealogyEngine
{
    private function maxDepth(): int
    {
        return max(1, (int) config('frosty.max_genealogy_depth', 4));
    }

    public function assignGenealogy(User $operator, ?User $sponsor = null): User
    {
        if ($sponsor !== null) {
            if ($sponsor->role !== UserRole::Operator) {
                throw new \InvalidArgumentException('Sponsor must be an operator.');
            }

            $operator->sponsor_id = $sponsor->id;
            $operator->genealogy_level = min(255, $sponsor->genealogy_level + 1);
            $base = $sponsor->genealogy_path ?? '/'.$sponsor->id.'/';
            $operator->genealogy_path = rtrim($base, '/').'/'.$operator->id.'/';
        } else {
            $operator->sponsor_id = null;
            $operator->genealogy_level = 0;
            $operator->genealogy_path = '/'.$operator->id.'/';
        }

        $operator->save();

        return $operator;
    }

    /**
     * @return array<int, Collection<int, User>>
     */
    public function downlinesByLevel(User $operator, ?int $maxLevel = null): array
    {
        $maxLevel ??= $this->maxDepth();
        $levels = [];
        $currentIds = [$operator->id];

        for ($level = 1; $level <= $maxLevel; $level++) {
            $downlines = User::query()
                ->whereIn('sponsor_id', $currentIds)
                ->where('role', UserRole::Operator)
                ->orderBy('name')
                ->get();

            $levels[$level] = $downlines;
            $currentIds = $downlines->pluck('id')->all();

            if ($currentIds === []) {
                for ($fill = $level + 1; $fill <= $maxLevel; $fill++) {
                    $levels[$fill] = collect();
                }
                break;
            }
        }

        return $levels;
    }

    /**
     * @return array<int, User|null>
     */
    public function uplineChain(User $buyer, ?int $maxLevel = null): array
    {
        $maxLevel ??= $this->maxDepth();
        $chain = [];
        $referrerId = $buyer->sponsor_id;

        for ($level = 1; $level <= $maxLevel; $level++) {
            if (! $referrerId) {
                $chain[$level] = null;
                continue;
            }

            $upline = User::query()->find($referrerId);
            $chain[$level] = $upline;
            $referrerId = $upline?->sponsor_id;
        }

        return $chain;
    }
}
