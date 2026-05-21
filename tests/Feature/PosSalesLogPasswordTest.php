<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PosSalesLogPasswordTest extends TestCase
{
    use RefreshDatabase;

    public function test_pos_logs_unlock_with_super_admin_password(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::SuperAdmin,
            'password' => 'SecretPass123!',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.pos-sales-logs.secure'))
            ->assertOk();

        $this->actingAs($admin)
            ->post(route('admin.pos-sales-logs.unlock'), ['password' => 'SecretPass123!'])
            ->assertRedirect(route('admin.pos-sales-logs.index'));

        $this->actingAs($admin)
            ->get(route('admin.pos-sales-logs.index'))
            ->assertOk();
    }

    public function test_pos_logs_rejects_wrong_password(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::SuperAdmin,
            'password' => 'SecretPass123!',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.pos-sales-logs.unlock'), ['password' => 'wrong-password'])
            ->assertSessionHasErrors('password');

        $this->actingAs($admin)
            ->get(route('admin.pos-sales-logs.index'))
            ->assertRedirect(route('admin.pos-sales-logs.secure'));
    }
}
