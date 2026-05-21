<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Distributor;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

final class AdminOperatorService
{
    public function __construct(
        private readonly AdminUserService $users,
    ) {}

    /** @param  array<string, mixed>  $data */
    public function create(array $data): User
    {
        $data['role'] = UserRole::Operator->value;
        $data = $this->normalizeNameFields($data);

        if (! empty($data['distributor_id'])) {
            $dist = Distributor::query()->find($data['distributor_id']);
            if ($dist) {
                $data['region'] = $dist->operatorPriceRegion()->value;
            }
        }

        return $this->users->create($data);
    }

    /** @param  array<string, mixed>  $data */
    public function update(User $operator, array $data): User
    {
        $region = $data['region'] ?? $operator->region;
        if (! empty($data['distributor_id'])) {
            $dist = Distributor::query()->find($data['distributor_id']);
            if ($dist) {
                $region = $dist->operatorPriceRegion();
            }
        }

        $operator->update([
            'name' => $data['name'],
            'email' => $data['email'],
            'distributor_id' => $data['distributor_id'] ?? null,
            'status' => $data['status'] ?? $operator->status,
            'region' => $region,
        ]);

        return $operator->fresh();
    }

    public function toggleStatus(User $operator): User
    {
        $next = $operator->status === UserStatus::Active ? UserStatus::Inactive : UserStatus::Active;
        $operator->update(['status' => $next]);

        return $operator->fresh();
    }

    public function resetPassword(User $operator, string $password): void
    {
        $operator->update(['password' => $password]);
    }

    /** @param  array<string, mixed>  $data */
    private function normalizeNameFields(array $data): array
    {
        if (isset($data['name']) && empty($data['first_name'])) {
            $parts = preg_split('/\s+/', trim($data['name']), 2);
            $data['first_name'] = $parts[0];
            $data['last_name'] = $parts[1] ?? '';
        }

        return $data;
    }

    public function delete(User $operator): void
    {
        if ($operator->role !== UserRole::Operator) {
            throw new RuntimeException('User is not an operator.');
        }

        if (Order::query()->where('operator_id', $operator->id)->orWhere('user_id', $operator->id)->exists()) {
            throw new RuntimeException('Cannot delete operator with existing orders. Set inactive instead.');
        }

        if ($operator->referrals()->exists()) {
            throw new RuntimeException('Cannot delete operator with referrals in genealogy.');
        }

        $operator->delete();
    }
}
