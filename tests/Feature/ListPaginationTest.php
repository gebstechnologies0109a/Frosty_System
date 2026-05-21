<?php

namespace Tests\Feature;

use App\Enums\ProductCategory;
use App\Enums\UserRole;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ListPaginationTest extends TestCase
{
    use RefreshDatabase;

    public function test_products_index_uses_bootstrap_pagination_without_tailwind_svg(): void
    {
        $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);

        foreach (range(1, 15) as $i) {
            Product::query()->create([
                'name' => "Product {$i}",
                'category' => ProductCategory::Softserve,
                'points' => 1,
                'status' => 'active',
            ]);
        }

        $this->actingAs($admin)
            ->get(route('admin.products.index', ['per_page' => 10]))
            ->assertOk()
            ->assertSee('Items per page')
            ->assertSee('value="10"', false)
            ->assertSee('pagination', false)
            ->assertDontSee('stroke-width="2"', false);
    }

    public function test_invalid_per_page_falls_back_to_default(): void
    {
        $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);

        Product::query()->create([
            'name' => 'One',
            'category' => ProductCategory::Softserve,
            'points' => 1,
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin)
            ->get(route('admin.products.index', ['per_page' => 999]));

        $response->assertOk();
        $this->assertSame(20, $response->viewData('products')->perPage());
    }
}
