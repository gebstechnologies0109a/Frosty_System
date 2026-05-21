<?php

namespace Tests\Feature;

use App\Enums\StockLogAdjustmentType;
use App\Enums\UserRole;
use App\Models\Distributor;
use App\Models\Product;
use App\Models\ProductInventory;
use App\Models\ProductPrice;
use App\Models\StockLog;
use App\Models\User;
use App\Enums\PriceRegion;
use App\Enums\ProductCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DistributorInventoryAdjustmentTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{user: User, profile: Distributor} */
    private function distributorUser(): array
    {
        $user = User::factory()->create(['role' => UserRole::Distributor]);
        $profile = Distributor::query()->create([
            'name' => 'Test Dist',
            'user_id' => $user->id,
            'is_main' => false,
        ]);

        return ['user' => $user, 'profile' => $profile];
    }

    private function productWithStock(int $stock, float $price = 100): Product
    {
        $product = Product::query()->create([
            'name' => 'Test Product',
            'category' => ProductCategory::Softserve,
            'points' => 1,
            'status' => 'active',
        ]);

        foreach (PriceRegion::cases() as $region) {
            ProductPrice::query()->create([
                'product_id' => $product->id,
                'region' => $region,
                'price' => $price,
            ]);
        }

        ProductInventory::query()->create([
            'product_id' => $product->id,
            'stock' => $stock,
        ]);

        return $product;
    }

    private function validPayload(int $productId, string $type = 'add', int $qty = 5): array
    {
        return [
            'product_id' => $productId,
            'adjustment_type' => $type,
            'quantity' => $qty,
            'reason' => 'count',
            'remarks' => 'Cycle count adjustment for warehouse.',
        ];
    }

    public function test_adjust_page_loads_with_form_and_history(): void
    {
        ['user' => $distUser] = $this->distributorUser();
        $product = $this->productWithStock(10);

        $this->actingAs($distUser)
            ->get(route('distributor.inventory.adjust'))
            ->assertOk()
            ->assertSee('Inventory adjustment')
            ->assertSee('New adjustment')
            ->assertSee($product->name)
            ->assertSee('Adjustment history');
    }

    public function test_add_stock_creates_pending_log_and_applies_after_approval(): void
    {
        ['user' => $distUser, 'profile' => $profile] = $this->distributorUser();
        $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);
        $product = $this->productWithStock(10);

        $this->actingAs($distUser)
            ->post(route('distributor.inventory.adjust.store'), $this->validPayload($product->id, 'add', 5))
            ->assertRedirect(route('distributor.inventory.adjust'))
            ->assertSessionHas('success');

        $log = StockLog::query()->first();
        $this->assertNotNull($log);
        $this->assertSame($profile->id, $log->distributor_id);
        $this->assertSame(StockLogAdjustmentType::Add, $log->adjustment_type);
        $this->assertNull($log->approved_by);
        $this->assertSame(10, $product->fresh()->stockLevel());

        $this->actingAs($admin)
            ->post(route('admin.purchasing.stock-logs.approve', $log))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame(15, $product->fresh()->stockLevel());
        $this->assertSame($admin->id, $log->fresh()->approved_by);
    }

    public function test_deduct_stock_after_approval(): void
    {
        ['user' => $distUser] = $this->distributorUser();
        $admin = User::factory()->create(['role' => UserRole::PurchasingAdmin]);
        $product = $this->productWithStock(20);

        $this->actingAs($distUser)
            ->post(route('distributor.inventory.adjust.store'), $this->validPayload($product->id, 'deduct', 7))
            ->assertRedirect();

        $log = StockLog::query()->first();
        $this->assertSame(StockLogAdjustmentType::Deduct, $log->adjustment_type);

        $this->actingAs($admin)
            ->post(route('admin.purchasing.stock-logs.approve', $log));

        $this->assertSame(13, $product->fresh()->stockLevel());
    }

    public function test_invalid_quantity_zero_fails_validation(): void
    {
        ['user' => $distUser] = $this->distributorUser();
        $product = $this->productWithStock(10);

        $this->actingAs($distUser)
            ->post(route('distributor.inventory.adjust.store'), array_merge(
                $this->validPayload($product->id),
                ['quantity' => 0],
            ))
            ->assertSessionHasErrors('quantity');

        $this->assertSame(0, StockLog::query()->count());
    }

    public function test_missing_remarks_fails_validation(): void
    {
        ['user' => $distUser] = $this->distributorUser();
        $product = $this->productWithStock(10);

        $payload = $this->validPayload($product->id);
        $payload['remarks'] = '';

        $this->actingAs($distUser)
            ->post(route('distributor.inventory.adjust.store'), $payload)
            ->assertSessionHasErrors('remarks');

        $this->assertSame(0, StockLog::query()->count());
    }

    public function test_deduct_more_than_stock_fails_on_submit(): void
    {
        ['user' => $distUser] = $this->distributorUser();
        $product = $this->productWithStock(3);

        $this->actingAs($distUser)
            ->post(route('distributor.inventory.adjust.store'), $this->validPayload($product->id, 'deduct', 10))
            ->assertSessionHasErrors('quantity');

        $this->assertSame(0, StockLog::query()->count());
    }

    public function test_admin_reject_removes_pending_log(): void
    {
        ['user' => $distUser] = $this->distributorUser();
        $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);
        $product = $this->productWithStock(10);

        $this->actingAs($distUser)
            ->post(route('distributor.inventory.adjust.store'), $this->validPayload($product->id));

        $log = StockLog::query()->first();
        $this->assertNotNull($log);

        $this->actingAs($admin)
            ->post(route('admin.purchasing.stock-logs.reject', $log))
            ->assertSessionHas('success');

        $this->assertSame(0, StockLog::query()->count());
        $this->assertSame(10, $product->fresh()->stockLevel());
    }

    public function test_non_admin_cannot_approve_stock_log(): void
    {
        ['user' => $distUser] = $this->distributorUser();
        $product = $this->productWithStock(10);

        $this->actingAs($distUser)
            ->post(route('distributor.inventory.adjust.store'), $this->validPayload($product->id));

        $log = StockLog::query()->first();

        $this->actingAs($distUser)
            ->post(route('admin.purchasing.stock-logs.approve', $log))
            ->assertForbidden();
    }
}
