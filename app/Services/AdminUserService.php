<?php

namespace App\Services;

use App\Enums\PriceRegion;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Distributor;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

final class AdminUserService
{
    public function __construct(
        private readonly GenealogyEngine $genealogy,
        private readonly WalletService $wallets,
        private readonly OperatorProductDefaultsService $operatorDefaults,
    ) {}

    /** @param  array<string, mixed>  $data */
    public function create(array $data, ?UploadedFile $photo = null): User
    {
        return DB::transaction(function () use ($data, $photo) {
            $role = UserRole::from($data['role']);
            $name = User::fullNameFromParts($data['first_name'], $data['last_name'] ?? '');

            $user = User::query()->create([
                'name' => $name,
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'] ?? '',
                'email' => $data['email'],
                'password' => $data['password'],
                'role' => $role,
                'status' => UserStatus::from($data['status'] ?? UserStatus::Active->value),
                'region' => isset($data['region']) ? PriceRegion::from($data['region']) : PriceRegion::Luzon,
                'sponsor_id' => $this->resolveSponsorId($data, $role),
                'distributor_id' => $role === UserRole::Operator ? ($data['distributor_id'] ?? null) : null,
                'profile_photo_path' => $photo ? $this->storePhoto($photo) : null,
            ]);

            if ($role === UserRole::Distributor) {
                Distributor::query()->create([
                    'name' => $name,
                    'is_main' => false,
                    'user_id' => $user->id,
                ]);
            }

            if ($role === UserRole::Operator) {
                $sponsor = ! empty($data['sponsor_id'])
                    ? User::query()->find($data['sponsor_id'])
                    : null;
                $this->genealogy->assignGenealogy($user, $sponsor);
                $this->wallets->ensureWallet($user);
                $this->operatorDefaults->ensureDefaults($user);
            }

            return $user;
        });
    }

    /** @param  array<string, mixed>  $data */
    public function update(User $user, array $data, ?UploadedFile $photo = null): User
    {
        return DB::transaction(function () use ($user, $data, $photo) {
            $name = User::fullNameFromParts($data['first_name'], $data['last_name'] ?? '');
            $attrs = [
                'name' => $name,
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'] ?? '',
                'email' => $data['email'],
                'status' => UserStatus::from($data['status']),
            ];

            if (isset($data['role'])) {
                $attrs['role'] = UserRole::from($data['role']);
            }

            if ($user->isOperator() && isset($data['distributor_id'])) {
                $attrs['distributor_id'] = $data['distributor_id'];
            }

            if (isset($data['region']) && $user->isOperator()) {
                $attrs['region'] = PriceRegion::from($data['region']);
            }

            if (array_key_exists('sponsor_id', $data)) {
                $attrs['sponsor_id'] = $data['sponsor_id'] ?: null;
            }

            if ($photo) {
                if ($user->profile_photo_path) {
                    Storage::disk('public')->delete($user->profile_photo_path);
                }
                $attrs['profile_photo_path'] = $this->storePhoto($photo);
            }

            $user->update($attrs);

            if ($user->isDistributor() && $user->distributorProfile) {
                $user->distributorProfile->update(['name' => $name]);
            }

            return $user->fresh();
        });
    }

    public function resetPassword(User $user, string $password): void
    {
        $user->update(['password' => $password]);
    }

    public function delete(User $user): void
    {
        if ($user->role === UserRole::SuperAdmin && User::query()->where('role', UserRole::SuperAdmin)->count() <= 1) {
            throw new RuntimeException('Cannot delete the only Super Admin account.');
        }

        if ($user->isDistributor() && $user->distributorProfile?->is_main) {
            throw new RuntimeException('Cannot delete the main distributor.');
        }

        if (Order::query()->where('user_id', $user->id)->orWhere('operator_id', $user->id)->exists()) {
            throw new RuntimeException('Cannot delete user with existing orders.');
        }

        if ($user->referrals()->exists()) {
            throw new RuntimeException('Cannot delete user with referred operators in genealogy.');
        }

        DB::transaction(function () use ($user) {
            if ($user->profile_photo_path) {
                Storage::disk('public')->delete($user->profile_photo_path);
            }

            $user->distributorProfile?->delete();
            $user->delete();
        });
    }

    /** @param  array<string, mixed>  $data */
    private function resolveSponsorId(array $data, UserRole $role): ?int
    {
        if (! in_array($role, [UserRole::Operator, UserRole::Distributor], true)) {
            return null;
        }

        return ! empty($data['sponsor_id']) ? (int) $data['sponsor_id'] : null;
    }

    private function storePhoto(UploadedFile $photo): string
    {
        return $photo->store('profile-photos', 'public');
    }
}
