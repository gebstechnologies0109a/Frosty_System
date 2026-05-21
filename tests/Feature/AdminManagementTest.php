<?php

namespace Tests\Feature;

use App\Enums\OrderSource;
use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Enums\PriceRegion;
use App\Enums\UserRole;
use App\Enums\WithdrawalStatus;
use App\Services\WalletService;
use App\Models\Distributor;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\Withdrawal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_dashboard_stat_cards_link_to_modules(): void
    {
        $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee(route('admin.operators.index'), false)
            ->assertSee(route('admin.distributors.index'), false)
            ->assertSee(route('admin.users.index'), false)
            ->assertSee(route('admin.products.index'), false)
            ->assertSee(route('admin.pos.logs'), false)
            ->assertSee(route('admin.pos.closings'), false);
    }

    public function test_super_admin_can_access_management_indexes(): void
    {
        $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);
        $distributor = Distributor::query()->create(['name' => 'Dist', 'is_main' => false]);

        User::factory()->create([
            'role' => UserRole::Operator,
            'distributor_id' => $distributor->id,
        ]);

        User::factory()->create(['role' => UserRole::Distributor]);

        Product::query()->create([
            'name' => 'Test Product',
            'category' => 'supply',
            'points' => 0,
            'status' => 'active',
        ]);

        $this->actingAs($admin)->get(route('admin.operators.index'))->assertOk()->assertSee('Operators');
        $this->actingAs($admin)->get(route('admin.distributors.index'))->assertOk()->assertSee('Distributors');
        $this->actingAs($admin)->get(route('admin.products.index'))->assertOk()->assertSee('Test Product');
        $this->actingAs($admin)->get(route('admin.orders.pending'))->assertOk();
        $this->actingAs($admin)->get(route('admin.withdrawals.pending'))->assertOk();
    }

    public function test_non_super_admin_cannot_access_operator_management(): void
    {
        $finance = User::factory()->create(['role' => UserRole::FinanceAdmin]);

        $this->actingAs($finance)
            ->get(route('admin.operators.index'))
            ->assertForbidden();
    }

    public function test_pending_order_show_and_actions(): void
    {
        $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);
        $main = Distributor::query()->create(['name' => 'Main', 'is_main' => true]);
        $operator = User::factory()->create([
            'role' => UserRole::Operator,
            'distributor_id' => $main->id,
        ]);

        $order = Order::query()->create([
            'user_id' => $operator->id,
            'distributor_id' => $main->id,
            'status' => OrderStatus::Pending,
            'total_amount' => 100,
            'total_points' => 10,
            'source' => OrderSource::Operator,
            'order_type' => OrderType::Supply,
            'price_region' => PriceRegion::Luzon,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.orders.pending.show', $order))
            ->assertRedirect(route('admin.orders.show', $order));

        $this->actingAs($admin)
            ->get(route('admin.orders.show', $order))
            ->assertOk()
            ->assertSee('Order #'.$order->id);

        $this->actingAs($admin)
            ->post(route('admin.orders.pending.reject', $order))
            ->assertRedirect();

        $this->assertSame(OrderStatus::Rejected, $order->fresh()->status);
    }

    public function test_pending_withdrawal_approve(): void
    {
        $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);
        $operator = User::factory()->create(['role' => UserRole::Operator]);

        app(WalletService::class)->credit($operator, 100, 'test');

        $withdrawal = Withdrawal::query()->create([
            'user_id' => $operator->id,
            'amount' => 50,
            'status' => WithdrawalStatus::Pending,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.withdrawals.pending.approve', $withdrawal))
            ->assertRedirect(route('admin.withdrawals.pending'));

        $this->assertSame(WithdrawalStatus::Approved, $withdrawal->fresh()->status);
    }
}
