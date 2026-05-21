<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Distributor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_index_and_create_form(): void
    {
        $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('Users');

        $this->actingAs($admin)
            ->get(route('admin.users.create'))
            ->assertOk()
            ->assertSee('First name');
    }

    public function test_super_admin_can_create_user_with_first_and_last_name(): void
    {
        $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);
        $distributor = Distributor::query()->create(['name' => 'Dist', 'is_main' => false]);

        $this->actingAs($admin)
            ->post(route('admin.users.store'), [
                'first_name' => 'Jane',
                'last_name' => 'Operator',
                'email' => 'jane.op@frosty.test',
                'role' => UserRole::Operator->value,
                'status' => UserStatus::Active->value,
                'password' => 'Password123!',
                'distributor_id' => $distributor->id,
                'sponsor_id' => null,
            ])
            ->assertRedirect();

        $user = User::query()->where('email', 'jane.op@frosty.test')->first();
        $this->assertNotNull($user);
        $this->assertSame('Jane', $user->first_name);
        $this->assertSame('Operator', $user->last_name);
    }

    public function test_delete_requires_delete_confirmation(): void
    {
        $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);
        $target = User::factory()->create(['role' => UserRole::FinanceAdmin]);

        $this->actingAs($admin)
            ->delete(route('admin.users.destroy', $target), ['confirm_delete' => 'WRONG'])
            ->assertSessionHasErrors();

        $this->assertNotNull($target->fresh());
    }

    public function test_dashboard_links_include_users_and_pos_routes(): void
    {
        $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee(route('admin.users.index'), false)
            ->assertSee(route('admin.pos.logs'), false)
            ->assertSee(route('admin.pos.closings'), false);
    }
}
