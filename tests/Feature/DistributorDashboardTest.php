<?php

namespace Tests\Feature;

use App\Enums\DistributorPricingRegion;
use App\Enums\OrderSource;
use App\Enums\OrderStatus;
use App\Enums\PriceRegion;
use App\Enums\ProductCategory;
use App\Enums\UserRole;
use App\Models\Distributor;
use App\Models\OperatorInventory;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DistributorDashboardTest extends TestCase
{
    use RefreshDatabase;

    private function seedRegionalProduct(string $name, float $luzonPrice, float $davaoPrice): Product
    {
        $product = Product::query()->create([
            'name' => $name,
            'category' => ProductCategory::Softserve,
            'points' => 2,
            'status' => 'active',
        ]);

        ProductPrice::query()->create([
            'product_id' => $product->id,
            'region' => PriceRegion::Luzon,
            'price' => $luzonPrice,
        ]);

        ProductPrice::query()->create([
            'product_id' => $product->id,
            'region' => PriceRegion::Davao,
            'price' => $davaoPrice,
        ]);

        return $product;
    }

    /** @return array{user: User, profile: Distributor} */
    private function distributorWithRegion(DistributorPricingRegion $pricingRegion): array
    {
        $user = User::factory()->create(['role' => UserRole::Distributor]);
        $profile = Distributor::query()->create([
            'name' => $pricingRegion->label().' Hub',
            'user_id' => $user->id,
            'is_main' => false,
            'pricing_region' => $pricingRegion,
        ]);

        return ['user' => $user, 'profile' => $profile];
    }

    public function test_luzon_distributor_dashboard_loads_all_widgets(): void
    {
        ['user' => $distUser, 'profile' => $profile] = $this->distributorWithRegion(DistributorPricingRegion::Luzon);

        $operator = User::factory()->create([
            'role' => UserRole::Operator,
            'distributor_id' => $profile->id,
            'region' => PriceRegion::Luzon,
        ]);

        $this->seedRegionalProduct('Luzon Cone', 100, 120);

        Order::query()->create([
            'user_id' => $operator->id,
            'distributor_id' => $profile->id,
            'status' => OrderStatus::Pending,
            'total_amount' => 500,
            'total_points' => 2,
            'source' => OrderSource::Operator,
            'price_region' => PriceRegion::Luzon,
        ]);

        $this->actingAs($distUser)
            ->get(route('distributor.dashboard'))
            ->assertOk()
            ->assertSee('Luzon Hub')
            ->assertSee('Regional pricing: Luzon')
            ->assertSee('Operators')
            ->assertSee($operator->name)
            ->assertSee('Pending operator orders')
            ->assertSee('Luzon Cone')
            ->assertSee('₱100.00')
            ->assertSee('Order from Main');
    }

    public function test_mindanao_distributor_sees_mindanao_catalog_prices(): void
    {
        ['user' => $distUser, 'profile' => $profile] = $this->distributorWithRegion(DistributorPricingRegion::Mindanao);

        $this->seedRegionalProduct('Regional Mix', 200, 250);

        $this->actingAs($distUser)
            ->get(route('distributor.dashboard'))
            ->assertOk()
            ->assertSee('Regional pricing: Mindanao')
            ->assertSee('davao')
            ->assertSee('₱250.00')
            ->assertDontSee('₱200.00');
    }

    public function test_mindanao_operator_sees_mindanao_prices_in_search(): void
    {
        ['profile' => $profile] = $this->distributorWithRegion(DistributorPricingRegion::Mindanao);

        $operator = User::factory()->create([
            'role' => UserRole::Operator,
            'distributor_id' => $profile->id,
            'region' => PriceRegion::Davao,
        ]);

        $product = $this->seedRegionalProduct('Mindanao Special', 180, 220);

        $response = $this->actingAs($operator)
            ->getJson(route('operator.products.search', ['q' => 'Mindanao']))
            ->assertOk();

        $response->assertJsonFragment([
            'id' => $product->id,
            'price' => 220.0,
        ]);
    }

    public function test_luzon_operator_sees_luzon_prices_in_search(): void
    {
        $main = Distributor::query()->create(['name' => 'Main', 'is_main' => true, 'pricing_region' => DistributorPricingRegion::Luzon]);

        $operator = User::factory()->create([
            'role' => UserRole::Operator,
            'distributor_id' => $main->id,
            'region' => PriceRegion::Luzon,
        ]);

        $product = $this->seedRegionalProduct('Luzon Special', 150, 190);

        $response = $this->actingAs($operator)
            ->getJson(route('operator.products.search', ['q' => 'Luzon']))
            ->assertOk();

        $response->assertJsonFragment([
            'id' => $product->id,
            'price' => 150.0,
        ]);
    }

    public function test_distributor_inventory_page_loads(): void
    {
        ['user' => $distUser, 'profile' => $profile] = $this->distributorWithRegion(DistributorPricingRegion::Luzon);

        $operator = User::factory()->create([
            'role' => UserRole::Operator,
            'distributor_id' => $profile->id,
            'region' => PriceRegion::Luzon,
        ]);

        $product = $this->seedRegionalProduct('Stock Item', 50, 60);

        OperatorInventory::query()->create([
            'operator_id' => $operator->id,
            'product_id' => $product->id,
            'stock' => 5,
            'minimum_stock' => 10,
        ]);

        $this->actingAs($distUser)
            ->get(route('distributor.inventory.index'))
            ->assertOk()
            ->assertSee('Stock Item')
            ->assertSee($operator->name);
    }

    public function test_distributor_order_create_uses_regional_products(): void
    {
        ['user' => $distUser] = $this->distributorWithRegion(DistributorPricingRegion::Mindanao);

        $this->seedRegionalProduct('Only Davao', 100, 130);

        $this->actingAs($distUser)
            ->get(route('distributor.orders.create'))
            ->assertOk()
            ->assertSee('Only Davao');
    }

    public function test_guest_cannot_access_distributor_dashboard(): void
    {
        $this->get(route('distributor.dashboard'))->assertRedirect(route('login'));
    }
}
