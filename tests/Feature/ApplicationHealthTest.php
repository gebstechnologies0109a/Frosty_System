<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Distributor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApplicationHealthTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_login_page_loads(): void
    {
        $this->get(route('login'))->assertOk();
    }

    public function test_super_admin_dashboard_and_add_user_form_load(): void
    {
        $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Add User');

        $this->actingAs($admin)
            ->get(route('admin.users.create'))
            ->assertOk()
            ->assertSee('Add User')
            ->assertSee('First name');
    }

    public function test_operator_dashboard_mobile_layout_loads(): void
    {
        $distributor = Distributor::query()->create([
            'name' => 'Test Distributor',
            'is_main' => false,
        ]);

        $operator = User::factory()->create([
            'role' => UserRole::Operator,
            'distributor_id' => $distributor->id,
        ]);

        $this->actingAs($operator)
            ->get(route('operator.dashboard'))
            ->assertOk()
            ->assertSee('Today at a glance')
            ->assertSee('Inventory', false)
            ->assertDontSee('Daily closing', false);
    }

    public function test_operator_pos_page_loads_without_daily_closing_ui(): void
    {
        $distributor = Distributor::query()->create([
            'name' => 'Test Distributor',
            'is_main' => false,
        ]);

        $operator = User::factory()->create([
            'role' => UserRole::Operator,
            'distributor_id' => $distributor->id,
        ]);

        $this->actingAs($operator)
            ->get(route('operator.pos.index'))
            ->assertOk()
            ->assertSee('Frosty POS')
            ->assertDontSee('Daily Closing', false);
    }
}
