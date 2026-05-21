<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Distributor;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class AdminDistributorService
{
    public function __construct(
        private readonly AdminUserService $users,
    ) {}

    /** @param  array<string, mixed>  $data */
    public function create(array $data): User
    {
        $data['role'] = UserRole::Distributor->value;

        return $this->users->create($data);
    }

    /** @param  array<string, mixed>  $data */
    public function update(User $user, Distributor $distributor, array $data): User
    {
        return DB::transaction(function () use ($user, $distributor, $data) {
            $user->update([
                'name' => $data['name'],
                'email' => $data['email'],
                'status' => $data['status'] ?? $user->status,
            ]);

            $distributor->update([
                'name' => $data['name'],
                'is_main' => (bool) ($data['is_main'] ?? false),
            ]);

            return $user->fresh();
        });
    }

    public function toggleStatus(User $user): User
    {
        $next = $user->status === UserStatus::Active ? UserStatus::Inactive : UserStatus::Active;
        $user->update(['status' => $next]);

        return $user->fresh();
    }

    public function resetPassword(User $user, string $password): void
    {
        $user->update(['password' => $password]);
    }

    public function delete(User $user, Distributor $distributor): void
    {
        if ($distributor->is_main) {
            throw new RuntimeException('Cannot delete the main distributor.');
        }

        if (Order::query()->where('distributor_id', $distributor->id)->exists()) {
            throw new RuntimeException('Cannot delete distributor with orders.');
        }

        if ($distributor->assignedOperators()->exists()) {
            throw new RuntimeException('Reassign operators before deleting this distributor.');
        }

        DB::transaction(function () use ($user, $distributor) {
            $distributor->delete();
            $user->delete();
        });
    }
}
