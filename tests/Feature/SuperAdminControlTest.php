<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\AdminPage;
use App\Models\User;
use App\Services\AdminImpersonationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuperAdminControlTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_index_has_management_actions(): void
    {
        $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);
        $operator = User::factory()->create(['role' => UserRole::Operator]);

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('Actions')
            ->assertSee('Impersonate user')
            ->assertSee('Reset password')
            ->assertSee('Change role');
    }

    public function test_super_admin_can_toggle_status_and_change_role(): void
    {
        $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);
        $target = User::factory()->create(['role' => UserRole::FinanceAdmin, 'status' => UserStatus::Active]);

        $this->actingAs($admin)
            ->patch(route('admin.users.toggle-status', $target))
            ->assertRedirect();

        $this->assertSame(UserStatus::Inactive, $target->fresh()->status);

        $this->actingAs($admin)
            ->patch(route('admin.users.change-role', $target), ['role' => UserRole::ItAdmin->value])
            ->assertRedirect();

        $this->assertSame(UserRole::ItAdmin, $target->fresh()->role);
    }

    public function test_super_admin_can_impersonate_operator_and_stop(): void
    {
        $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);
        $operator = User::factory()->create(['role' => UserRole::Operator]);

        $this->actingAs($admin)
            ->post(route('admin.users.impersonate', $operator))
            ->assertRedirect(route('operator.dashboard'));

        $this->assertAuthenticatedAs($operator);
        $this->assertSame($admin->id, session(AdminImpersonationService::SESSION_IMPERSONATOR));

        $this->actingAs($operator)
            ->post(route('admin.users.stop-impersonate'))
            ->assertRedirect(route('admin.users.index'));

        $this->assertAuthenticatedAs($admin);
        $this->assertNull(session(AdminImpersonationService::SESSION_IMPERSONATOR));
    }

    public function test_cannot_impersonate_super_admin(): void
    {
        $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);
        $other = User::factory()->create(['role' => UserRole::SuperAdmin, 'email' => 'other@frosty.test']);

        $this->actingAs($admin)
            ->post(route('admin.users.impersonate', $other))
            ->assertSessionHasErrors('impersonate');
    }

    public function test_page_builder_crud_and_public_render(): void
    {
        $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);

        $this->actingAs($admin)
            ->get(route('admin.page-builder.index'))
            ->assertOk()
            ->assertSee('Page Builder')
            ->assertSee('All pages')
            ->assertSee('Total pages');

        $this->actingAs($admin)
            ->post(route('admin.page-builder.store'), [
                'title' => 'Test Page',
                'slug' => 'test-page',
                'status' => \App\Enums\AdminPageStatus::Published->value,
                'layout_json' => json_encode([
                    'blocks' => [
                        ['id' => '1', 'type' => 'text', 'content' => 'Hello builder'],
                    ],
                ]),
            ])
            ->assertRedirect();

        $page = AdminPage::query()->where('slug', 'test-page')->first();
        $this->assertNotNull($page);

        $this->get(route('pages.show', 'test-page'))
            ->assertOk()
            ->assertSee('Hello builder');

        $this->actingAs($admin)
            ->get(route('admin.page-builder.preview', $page))
            ->assertOk()
            ->assertSee('Hello builder');

        $this->actingAs($admin)
            ->put(route('admin.page-builder.update', $page), [
                'title' => 'Test Page Updated',
                'slug' => 'test-page',
                'status' => \App\Enums\AdminPageStatus::Published->value,
                'layout_json' => json_encode(['blocks' => [['id' => '1', 'type' => 'text', 'content' => 'Done']]]),
                'finish' => '1',
            ])
            ->assertRedirect(route('admin.page-builder.index'))
            ->assertSessionHas('success');

        $this->assertSame('Test Page Updated', $page->fresh()->title);
    }

    public function test_non_super_admin_cannot_access_page_builder(): void
    {
        $finance = User::factory()->create(['role' => UserRole::FinanceAdmin]);

        $this->actingAs($finance)
            ->get(route('admin.page-builder.index'))
            ->assertForbidden();
    }
}
