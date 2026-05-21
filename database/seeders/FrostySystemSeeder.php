<?php

namespace Database\Seeders;

use App\Enums\ProductCategory;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Distributor;
use App\Models\Product;
use App\Models\User;
use App\Services\GenealogyEngine;
use App\Services\OperatorProductDefaultsService;
use App\Services\WalletService;
use App\Support\ProductCatalog;
use App\Support\ProductCatalogImporter;
use App\Support\ProductInventoryService;
use App\Support\ProductRegionalPricing;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class FrostySystemSeeder extends Seeder
{
    public function run(
        GenealogyEngine $genealogy,
        WalletService $wallets,
        ProductCatalogImporter $catalogImporter,
        OperatorProductDefaultsService $productDefaults,
    ): void
    {
        $password = Hash::make('password');

        if (! Distributor::query()->where('is_main', true)->exists()) {
            Distributor::query()->create([
                'id' => Distributor::mainId(),
                'name' => 'Main',
                'is_main' => true,
                'user_id' => null,
            ]);
        }

        $this->user('Super Admin', 'super@frosty.local', UserRole::SuperAdmin, $password);
        $this->user('Purchasing Admin', 'purchasing@frosty.local', UserRole::PurchasingAdmin, $password);
        $this->user('Finance Admin', 'finance@frosty.local', UserRole::FinanceAdmin, $password);
        $this->user('IT Admin', 'it@frosty.local', UserRole::ItAdmin, $password);

        $distributorUser = $this->user('Metro Distributor', 'distributor@frosty.local', UserRole::Distributor, $password);

        $metro = Distributor::query()->firstOrCreate(
            ['name' => 'Metro Distributor'],
            ['is_main' => false, 'user_id' => $distributorUser->id],
        );

        $operatorA = User::query()->firstOrCreate(
            ['email' => 'ana@frosty.local'],
            [
                'name' => 'Ana Operator',
                'password' => $password,
                'role' => UserRole::Operator,
                'status' => UserStatus::Active,
                'distributor_id' => $metro->id,
                'region' => 'luzon',
            ],
        );
        $genealogy->assignGenealogy($operatorA);
        $wallets->ensureWallet($operatorA);

        $operatorB = User::query()->firstOrCreate(
            ['email' => 'ben@frosty.local'],
            [
                'name' => 'Ben Operator',
                'password' => $password,
                'role' => UserRole::Operator,
                'status' => UserStatus::Active,
                'distributor_id' => $metro->id,
                'region' => 'luzon',
            ],
        );
        $genealogy->assignGenealogy($operatorB, $operatorA);
        $wallets->ensureWallet($operatorB);
        $productDefaults->ensureDefaults($operatorB);

        $this->seedFormulaProducts();
        $this->importSupplyCatalog($catalogImporter);

        Product::query()->where('category', 'cone')->update(['category' => 'supply']);

        Product::query()
            ->whereIn('name', ['Chocolate Syrup', 'Chocolate Dip'])
            ->where('category', 'supply')
            ->update(['status' => 'inactive']);
    }

    private function seedFormulaProducts(): void
    {
        foreach (ProductCatalog::formulaProducts() as $item) {
            $product = Product::query()->updateOrCreate(
                ['name' => $item['name']],
                [
                    'category' => $item['category'],
                    'points' => $item['points'],
                    'status' => 'active',
                ],
            );

            ProductRegionalPricing::sync($product, $item['prices']);
            ProductInventoryService::ensure($product);
        }

        Product::query()->doesntHave('inventory')->each(
            fn (Product $product) => ProductInventoryService::ensure($product),
        );

        Product::query()
            ->whereIn('name', [
                'Vanilla Softserve',
                'Chocolate Softserve',
                'Ube Softserve',
                'Strawberry Softserve',
                'Mango Softserve',
            ])
            ->update(['status' => 'inactive']);
    }

    private function importSupplyCatalog(ProductCatalogImporter $importer): void
    {
        $path = ProductCatalogImporter::defaultSupplyCatalogPath();

        if (is_readable($path)) {
            $importer->importFromJsonFile($path);
        }
    }

    private function user(string $name, string $email, UserRole $role, string $password): User
    {
        return User::query()->firstOrCreate(
            ['email' => $email],
            ['name' => $name, 'password' => $password, 'role' => $role, 'status' => UserStatus::Active, 'region' => 'luzon'],
        );
    }
}
