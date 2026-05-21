<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Enums\UserStatus;
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

        return $this->users->create($data);
    }

    /** @param  array<string, mixed>  $data */
    public function update(User $operator, array $data): User
    {
        $operator->update([
            'name' => $data['name'],
            'email' => $data['email'],
            'distributor_id' => $data['distributor_id'] ?? null,
            'status' => $data['status'] ?? $operator->status,
            'region' => $data['region'] ?? $operator->region,
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
