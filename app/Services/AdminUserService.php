<?php

namespace App\Services;

use App\Enums\PriceRegion;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Distributor;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class AdminUserService
{
    public function __construct(
        private readonly GenealogyEngine $genealogy,
        private readonly WalletService $wallets,
        private readonly OperatorProductDefaultsService $operatorDefaults,
    ) {}

    /**
     * @param  array{name: string, email: string, password: string, role: string, distributor_id?: int|null}  $data
     */
    public function create(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $role = UserRole::from($data['role']);

            $user = User::query()->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'role' => $role,
                'status' => UserStatus::Active,
                'region' => PriceRegion::Luzon,
                'distributor_id' => $role === UserRole::Operator ? ($data['distributor_id'] ?? null) : null,
            ]);

            if ($role === UserRole::Distributor) {
                Distributor::query()->create([
                    'name' => $data['name'],
                    'is_main' => false,
                    'user_id' => $user->id,
                ]);
            }

            if ($role === UserRole::Operator) {
                $this->genealogy->assignGenealogy($user);
                $this->wallets->ensureWallet($user);
                $this->operatorDefaults->ensureDefaults($user);
            }

            return $user;
        });
    }
}
