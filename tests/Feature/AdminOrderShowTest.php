<?php

namespace Tests\Feature;

use App\Enums\OrderSource;
use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Enums\PriceRegion;
use App\Enums\ProductCategory;
use App\Enums\UserRole;
use App\Models\Distributor;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminOrderShowTest extends TestCase
{
    use RefreshDatabase;

    private function createOrder(OrderStatus $status, bool $withItems = true): Order
    {
        $main = Distributor::query()->create(['name' => 'Main', 'is_main' => true]);
        $operator = User::factory()->create([
            'role' => UserRole::Operator,
            'distributor_id' => $main->id,
        ]);

        $order = Order::query()->create([
            'user_id' => $operator->id,
            'operator_id' => $operator->id,
            'distributor_id' => $main->id,
            'status' => $status,
            'total_amount' => 250,
            'total_points' => 25,
            'cogs_total' => 100,
            'gross_profit' => 150,
            'source' => OrderSource::Operator,
            'order_type' => OrderType::Supply,
            'price_region' => PriceRegion::Luzon,
        ]);

        if ($withItems) {
            $product = Product::query()->create([
                'name' => 'Frosty Mix',
                'category' => ProductCategory::Supply,
                'points' => 10,
                'status' => 'active',
            ]);

            OrderItem::query()->create([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'qty' => 2,
                'price' => 125,
                'line_total' => 250,
                'points' => 25,
            ]);
        }

        return $order;
    }

    public function test_admin_orders_show_route_loads_all_statuses(): void
    {
        $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);

        foreach ([OrderStatus::Pending, OrderStatus::Approved, OrderStatus::Completed, OrderStatus::Rejected] as $status) {
            $order = $this->createOrder($status);

            $this->actingAs($admin)
                ->get(route('admin.orders.show', $order))
                ->assertOk()
                ->assertSee('Order #'.$order->id)
                ->assertSee('Frosty Mix')
                ->assertSee(ucfirst($status->value));
        }
    }

    public function test_order_show_without_items(): void
    {
        $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);
        $order = $this->createOrder(OrderStatus::Pending, withItems: false);

        $this->actingAs($admin)
            ->get(route('admin.orders.show', $order))
            ->assertOk()
            ->assertSee('No line items on this order.');
    }

    public function test_orders_index_has_view_links(): void
    {
        $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);
        $order = $this->createOrder(OrderStatus::Pending);

        $this->actingAs($admin)
            ->get(route('admin.orders.index'))
            ->assertOk()
            ->assertSee(route('admin.orders.show', $order, false));
    }

    public function test_pending_show_redirects_to_orders_show(): void
    {
        $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);
        $order = $this->createOrder(OrderStatus::Pending);

        $this->actingAs($admin)
            ->get(route('admin.orders.pending.show', $order))
            ->assertRedirect(route('admin.orders.show', $order));
    }

    public function test_approve_and_reject_from_show_page(): void
    {
        $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);
        $order = $this->createOrder(OrderStatus::Pending);

        $this->actingAs($admin)
            ->post(route('admin.orders.approve', $order))
            ->assertRedirect()
            ->assertSessionHas('error');

        $order->update(['payment_proof_path' => 'order_payments/test-proof.jpg']);

        $this->actingAs($admin)
            ->post(route('admin.orders.approve', $order))
            ->assertRedirect();

        $this->assertSame(OrderStatus::Approved, $order->fresh()->status);

        $order2 = $this->createOrder(OrderStatus::Pending);
        $this->actingAs($admin)
            ->post(route('admin.orders.reject', $order2))
            ->assertRedirect();

        $this->assertSame(OrderStatus::Rejected, $order2->fresh()->status);
    }
}
