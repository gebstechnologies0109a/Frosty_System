<?php

namespace Tests\Feature;

use App\Enums\PriceRegion;
use App\Enums\ProductCategory;
use App\Enums\UserRole;
use App\Models\Distributor;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OperatorProductSearchTest extends TestCase
{
    use RefreshDatabase;

    private function operatorUser(): User
    {
        $main = Distributor::query()->create(['name' => 'Main', 'is_main' => true]);

        return User::factory()->create([
            'role' => UserRole::Operator,
            'distributor_id' => $main->id,
            'region' => PriceRegion::Luzon,
        ]);
    }

    private function seedProduct(string $name, ProductCategory $category, int $points = 2): Product
    {
        $product = Product::query()->create([
            'name' => $name,
            'category' => $category,
            'points' => $points,
            'status' => 'active',
        ]);

        foreach (PriceRegion::cases() as $region) {
            ProductPrice::query()->create([
                'product_id' => $product->id,
                'region' => $region,
                'price' => 241.50,
            ]);
        }

        return $product;
    }

    public function test_search_requires_at_least_two_characters(): void
    {
        $operator = $this->operatorUser();
        $this->seedProduct('Chocolate Softserve 1kg', ProductCategory::Softserve);

        $this->actingAs($operator)
            ->getJson(route('operator.products.search', ['q' => 'c']))
            ->assertOk()
            ->assertExactJson([]);
    }

    public function test_search_returns_chocolate_products(): void
    {
        $operator = $this->operatorUser();
        $choco = $this->seedProduct('Chocolate Softserve 1kg', ProductCategory::Softserve, 2);
        $this->seedProduct('Vanilla Syrup 1kg', ProductCategory::Syrup, 0);

        $response = $this->actingAs($operator)
            ->getJson(route('operator.products.search', ['q' => 'choco']))
            ->assertOk();

        $response->assertJsonFragment(['id' => $choco->id, 'name' => 'Chocolate Softserve 1kg']);
        $response->assertJsonMissing(['name' => 'Vanilla Syrup 1kg']);
    }

    public function test_search_returns_cone_products(): void
    {
        $operator = $this->operatorUser();
        $cone = $this->seedProduct('Waffle Cone Box', ProductCategory::Cone, 0);
        $this->seedProduct('Chocolate Softserve 1kg', ProductCategory::Softserve);

        $this->actingAs($operator)
            ->getJson(route('operator.products.search', ['q' => 'cone']))
            ->assertOk()
            ->assertJsonFragment(['id' => $cone->id]);
    }

    public function test_search_result_includes_price_and_points(): void
    {
        $operator = $this->operatorUser();
        $product = $this->seedProduct('Chocolate Softserve 1kg', ProductCategory::Softserve, 2);

        $this->actingAs($operator)
            ->getJson(route('operator.products.search', ['q' => 'choco']))
            ->assertOk()
            ->assertJsonFragment([
                'id' => $product->id,
                'price' => 241.5,
                'points' => 2,
                'category' => 'softserve',
            ]);
    }

    public function test_operator_create_page_uses_product_search_ui(): void
    {
        $operator = $this->operatorUser();

        $this->actingAs($operator)
            ->get(route('operator.orders.create'))
            ->assertOk()
            ->assertSee('Search products by name', false)
            ->assertSee('product-search-input', false)
            ->assertDontSee('class="form-select product-select"', false);
    }

    public function test_operator_can_submit_order_with_searched_products(): void
    {
        $operator = $this->operatorUser();
        $main = Distributor::query()->where('is_main', true)->first();
        $p1 = $this->seedProduct('Chocolate Softserve 1kg', ProductCategory::Softserve);
        $p2 = $this->seedProduct('Waffle Cone Box', ProductCategory::Cone, 0);

        $this->actingAs($operator)
            ->post(route('operator.orders.store'), [
                'distributor_id' => $main->id,
                'items' => [
                    ['product_id' => $p1->id, 'qty' => 2],
                    ['product_id' => $p2->id, 'qty' => 1],
                ],
            ])
            ->assertRedirect(route('operator.orders.index'));

        $this->assertDatabaseHas('order_items', ['product_id' => $p1->id, 'qty' => 2]);
        $this->assertDatabaseHas('order_items', ['product_id' => $p2->id, 'qty' => 1]);
    }

    public function test_store_rejects_product_without_regional_price(): void
    {
        $operator = $this->operatorUser();
        $main = Distributor::query()->where('is_main', true)->first();

        $product = Product::query()->create([
            'name' => 'Unpriced Item',
            'category' => ProductCategory::Supply,
            'points' => 0,
            'status' => 'active',
        ]);

        ProductPrice::query()->create([
            'product_id' => $product->id,
            'region' => PriceRegion::Davao,
            'price' => 100,
        ]);

        $this->actingAs($operator)
            ->post(route('operator.orders.store'), [
                'distributor_id' => $main->id,
                'items' => [['product_id' => $product->id, 'qty' => 1]],
            ])
            ->assertSessionHasErrors('items.0.product_id');
    }

    public function test_guest_cannot_search_products(): void
    {
        $this->getJson(route('operator.products.search', ['q' => 'choco']))
            ->assertUnauthorized();
    }
}
