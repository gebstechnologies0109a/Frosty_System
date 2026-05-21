<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PosSalesLogAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_access_pos_logs_directly(): void
    {
        $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);

        $this->actingAs($admin)
            ->get(route('admin.pos-sales-logs.index'))
            ->assertOk();
    }

    public function test_legacy_secure_url_redirects_to_index(): void
    {
        $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);

        $this->actingAs($admin)
            ->get(route('admin.pos-sales-logs.secure'))
            ->assertRedirect(route('admin.pos-sales-logs.index'));
    }

    public function test_non_super_admin_cannot_access_pos_logs(): void
    {
        $operator = User::factory()->create(['role' => UserRole::Operator]);

        $this->actingAs($operator)
            ->get(route('admin.pos-sales-logs.index'))
            ->assertForbidden();
    }
}
