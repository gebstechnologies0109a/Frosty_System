<?php

namespace Tests\Feature;

use App\Enums\PriceRegion;
use App\Enums\ProductCategory;
use App\Enums\UserRole;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AdminProductBulkUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_products_index_shows_bulk_edit_for_admins(): void
    {
        $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);
        Product::query()->create([
            'name' => 'Bulk Test',
            'category' => ProductCategory::Supply,
            'points' => 0,
            'status' => 'active',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.products.index'))
            ->assertOk()
            ->assertSee('Bulk Edit', false)
            ->assertSee('product-checkbox', false)
            ->assertSee('id="selectAll"', false);
    }

    #[DataProvider('adminRolesProvider')]
    public function test_admin_roles_can_bulk_update_status(UserRole $role): void
    {
        $user = User::factory()->create(['role' => $role]);
        $product = $this->createProductWithPrices();

        $this->actingAs($user)
            ->post(route('admin.products.bulk-update'), [
                'product_ids' => [$product->id],
                'status' => 'inactive',
            ])
            ->assertRedirect(route('admin.products.index'))
            ->assertSessionHas('success');

        $this->assertSame('inactive', $product->fresh()->status);
        $this->assertSame(100.0, (float) $product->fresh()->priceForRegion(PriceRegion::Luzon));
    }

    public function test_bulk_update_only_changes_filled_price_fields(): void
    {
        $admin = User::factory()->create(['role' => UserRole::PurchasingAdmin]);
        $product = $this->createProductWithPrices();

        $this->actingAs($admin)
            ->post(route('admin.products.bulk-update'), [
                'product_ids' => [$product->id],
                'price_luzon' => 150,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $product->refresh()->load('prices');
        $this->assertSame(150.0, $product->priceForRegion(PriceRegion::Luzon));
        $this->assertSame(80.0, $product->priceForRegion(PriceRegion::Davao));
        $this->assertSame(90.0, $product->priceForRegion(PriceRegion::Tacloban));
    }

    public function test_bulk_update_requires_at_least_one_field(): void
    {
        $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);
        $product = $this->createProductWithPrices();

        $this->actingAs($admin)
            ->from(route('admin.products.index'))
            ->post(route('admin.products.bulk-update'), [
                'product_ids' => [$product->id],
            ])
            ->assertRedirect(route('admin.products.index'))
            ->assertSessionHasErrors('bulk');
    }

    public function test_operator_cannot_bulk_update(): void
    {
        $operator = User::factory()->create(['role' => UserRole::Operator]);
        $product = $this->createProductWithPrices();

        $this->actingAs($operator)
            ->post(route('admin.products.bulk-update'), [
                'product_ids' => [$product->id],
                'status' => 'inactive',
            ])
            ->assertForbidden();
    }

    /** @return array<string, array{0: UserRole}> */
    public static function adminRolesProvider(): array
    {
        return [
            'super_admin' => [UserRole::SuperAdmin],
            'purchasing_admin' => [UserRole::PurchasingAdmin],
            'finance_admin' => [UserRole::FinanceAdmin],
            'it_admin' => [UserRole::ItAdmin],
        ];
    }

    private function createProductWithPrices(): Product
    {
        $product = Product::query()->create([
            'name' => 'Regional Product',
            'category' => ProductCategory::Supply,
            'points' => 0,
            'status' => 'active',
        ]);

        foreach ([
            PriceRegion::Luzon->value => 100,
            PriceRegion::Davao->value => 80,
            PriceRegion::Tacloban->value => 90,
        ] as $region => $price) {
            ProductPrice::query()->create([
                'product_id' => $product->id,
                'region' => $region,
                'price' => $price,
            ]);
        }

        return $product;
    }
}
