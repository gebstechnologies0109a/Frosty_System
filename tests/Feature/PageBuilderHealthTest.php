<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\AdminPage;
use App\Models\User;
use App\Services\SystemPagesRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PageBuilderHealthTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_system_pages_edit_preview_and_live_routes(): void
    {
        $this->artisan('frosty:sync-pages')->assertSuccessful();

        $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);

        $this->actingAs($admin)
            ->get(route('admin.page-builder.index'))
            ->assertOk();

        foreach (AdminPage::query()->where('is_system', true)->get() as $page) {
            $this->actingAs($admin)
                ->get(route('admin.page-builder.edit', $page))
                ->assertOk()
                ->assertSee($page->title, false);

            $this->actingAs($admin)
                ->get(route('admin.page-builder.preview', $page))
                ->assertOk();

            $this->assertTrue($page->canOpenLive(), "Page {$page->slug} should have openable live URL");

            $this->actingAs($admin)
                ->followingRedirects()
                ->get($page->liveUrl())
                ->assertOk();
        }
    }

    public function test_all_page_builder_actions_on_index(): void
    {
        $this->artisan('frosty:sync-pages');

        $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);
        $page = AdminPage::query()->where('slug', 'admin-products')->firstOrFail();

        $this->actingAs($admin)
            ->post(route('admin.page-builder.duplicate', $page))
            ->assertRedirect();

        $copy = AdminPage::query()->where('slug', 'like', 'admin-products-copy%')->first();
        $this->assertNotNull($copy);

        $this->actingAs($admin)
            ->get(route('admin.page-builder.edit', $copy))
            ->assertOk();

        $this->actingAs($admin)
            ->patch(route('admin.page-builder.toggle-status', $copy))
            ->assertRedirect();

        $this->actingAs($admin)
            ->delete(route('admin.page-builder.destroy', $copy))
            ->assertRedirect(route('admin.page-builder.manage'));
    }

    public function test_custom_page_public_route(): void
    {
        $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);

        $this->actingAs($admin)
            ->post(route('admin.page-builder.store'), [
                'title' => 'Public Test',
                'slug' => 'public-test',
                'status' => 'published',
                'layout_json' => json_encode([
                    'blocks' => [['id' => '1', 'type' => 'text', 'content' => 'Public content']],
                ]),
            ])
            ->assertRedirect();

        $this->get(route('pages.show', 'public-test'))
            ->assertOk()
            ->assertSee('Public content');
    }

    public function test_system_page_slug_redirects_to_admin_route(): void
    {
        $this->artisan('frosty:sync-pages');
        $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);

        $this->actingAs($admin)
            ->get(route('pages.show', 'admin-dashboard'))
            ->assertRedirect(route('admin.dashboard'));
    }

    public function test_sync_repairs_missing_route_metadata(): void
    {
        $this->artisan('frosty:sync-pages');

        $page = AdminPage::query()->where('slug', 'admin-users')->firstOrFail();
        $page->update(['route_name' => null, 'path' => null, 'is_system' => false]);

        $this->artisan('frosty:sync-pages')->assertSuccessful();

        $page->refresh();
        $this->assertSame('admin.users.index', $page->route_name);
        $this->assertSame('/admin/users', $page->path);
        $this->assertTrue($page->is_system);
    }
}
