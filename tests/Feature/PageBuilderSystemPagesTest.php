<?php

namespace Tests\Feature;

use App\Enums\AdminPageStatus;
use App\Enums\UserRole;
use App\Models\AdminPage;
use App\Models\User;
use App\Services\SystemPagesRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PageBuilderSystemPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('frosty:sync-pages');
    }

    public function test_page_builder_lists_all_seeded_system_pages(): void
    {
        $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);

        $this->actingAs($admin)
            ->get(route('admin.page-builder.index'))
            ->assertOk()
            ->assertSee('Total pages')
            ->assertSee('All pages ('.count(SystemPagesRegistry::pages()).')')
            ->assertSee('Admin Dashboard')
            ->assertSee('Super Admin Users')
            ->assertSee('Super Admin Operators')
            ->assertSee('Super Admin Distributors')
            ->assertSee('admin-users')
            ->assertSee('Published')
            ->assertSee('Edit');
    }

    public function test_super_admin_can_edit_system_page_and_save(): void
    {
        $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);
        $page = AdminPage::query()->where('slug', 'admin-users')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('admin.page-builder.edit', $page))
            ->assertOk()
            ->assertSee('Super Admin Users');

        $this->actingAs($admin)
            ->put(route('admin.page-builder.update', $page), [
                'title' => 'Super Admin Users',
                'slug' => 'admin-users',
                'status' => AdminPageStatus::Published->value,
                'layout_json' => json_encode([
                    'blocks' => [
                        ['id' => '1', 'type' => 'text', 'content' => 'Custom users header'],
                    ],
                ]),
                'finish' => '1',
            ])
            ->assertRedirect(route('admin.page-builder.index'));

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('Custom users header');
    }

    public function test_duplicate_and_publish_custom_page(): void
    {
        $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);
        $page = AdminPage::query()->where('slug', 'admin-dashboard')->firstOrFail();

        $this->actingAs($admin)
            ->post(route('admin.page-builder.duplicate', $page))
            ->assertRedirect();

        $copy = AdminPage::query()->where('slug', 'like', 'admin-dashboard-copy%')->first();
        $this->assertNotNull($copy);
        $this->assertFalse($copy->is_system);
        $this->assertSame(AdminPageStatus::Draft, $copy->status);
    }

    public function test_sync_command_is_idempotent(): void
    {
        $count = AdminPage::query()->count();
        $this->artisan('frosty:sync-pages')->assertSuccessful();
        $this->assertSame($count, AdminPage::query()->count());
        $this->assertSame(count(SystemPagesRegistry::pages()), AdminPage::query()->where('is_system', true)->count());

        $page = AdminPage::query()->where('slug', 'admin-operators')->firstOrFail();
        $this->assertSame('admin.operators.index', $page->route_name);
    }

    public function test_non_super_admin_cannot_sync_or_access_builder(): void
    {
        $finance = User::factory()->create(['role' => UserRole::FinanceAdmin]);

        $this->actingAs($finance)
            ->post(route('admin.page-builder.sync'))
            ->assertForbidden();
    }

    public function test_cannot_delete_system_page(): void
    {
        $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);
        $page = AdminPage::query()->where('slug', 'admin-dashboard')->firstOrFail();

        $this->actingAs($admin)
            ->delete(route('admin.page-builder.destroy', $page))
            ->assertSessionHasErrors('delete');

        $this->assertNotNull($page->fresh());
    }
}
