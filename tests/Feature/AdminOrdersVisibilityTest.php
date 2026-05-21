<?php

namespace Tests\Feature;

use App\Enums\OrderSource;
use App\Enums\OrderStatus;
use App\Enums\PriceRegion;
use App\Enums\UserRole;
use App\Models\Distributor;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminOrdersVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_orders_index_lists_regional_orders_by_latest_id(): void
    {
        $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);
        $main = Distributor::query()->create(['name' => 'Main', 'is_main' => true]);
        $regional = Distributor::query()->create(['name' => 'General Santos City', 'is_main' => false]);
        $operator = User::factory()->create(['role' => UserRole::Operator, 'distributor_id' => $regional->id]);

        Order::query()->create([
            'user_id' => $operator->id,
            'distributor_id' => $main->id,
            'status' => OrderStatus::Approved,
            'total_amount' => 100,
            'total_points' => 0,
            'source' => OrderSource::Operator,
            'price_region' => PriceRegion::Luzon,
        ]);

        $regionalOrder = Order::query()->create([
            'user_id' => $operator->id,
            'distributor_id' => $regional->id,
            'status' => OrderStatus::Pending,
            'total_amount' => 198.50,
            'total_points' => 2,
            'source' => OrderSource::Operator,
            'price_region' => PriceRegion::Davao,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.orders.index'))
            ->assertOk()
            ->assertSee('All orders', false)
            ->assertSee('#'.$regionalOrder->id, false)
            ->assertSee('General Santos City', false);
    }

    public function test_admin_dashboard_shows_recent_orders_including_high_ids(): void
    {
        $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);
        $distributor = Distributor::query()->create(['name' => 'GSC', 'is_main' => false]);
        $operator = User::factory()->create(['role' => UserRole::Operator, 'distributor_id' => $distributor->id]);

        Order::query()->create([
            'user_id' => $operator->id,
            'distributor_id' => $distributor->id,
            'status' => OrderStatus::Pending,
            'total_amount' => 50,
            'total_points' => 0,
            'source' => OrderSource::Operator,
            'price_region' => PriceRegion::Davao,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Recent supply orders', false);
    }

    public function test_admin_orders_index_includes_all_order_types(): void
    {
        $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);
        $distributor = Distributor::query()->create(['name' => 'Main', 'is_main' => true]);
        $operator = User::factory()->create(['role' => UserRole::Operator, 'distributor_id' => $distributor->id]);

        $posOrder = Order::query()->create([
            'user_id' => $operator->id,
            'operator_id' => $operator->id,
            'distributor_id' => $distributor->id,
            'status' => OrderStatus::Completed,
            'total_amount' => 25,
            'total_points' => 0,
            'source' => OrderSource::Operator,
            'order_type' => \App\Enums\OrderType::Pos,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.orders.index'))
            ->assertOk()
            ->assertSee('#'.$posOrder->id, false);
    }
}
