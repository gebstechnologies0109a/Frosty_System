<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\AdminPage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PageBuilderWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_workspace_loads_for_super_admin(): void
    {
        $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);

        $this->actingAs($admin)
            ->get(route('admin.page-builder.index'))
            ->assertOk()
            ->assertSee('Page Builder', false)
            ->assertSee('Components', false)
            ->assertSee('Canvas', false);
    }

    public function test_page_builder_json_crud_flow(): void
    {
        $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);

        $create = $this->actingAs($admin)
            ->postJson(route('admin.page-builder.pages.store'), [
                'name' => 'Test Landing',
                'slug' => 'test-landing',
            ])
            ->assertCreated()
            ->json('page');

        $pageId = $create['id'];

        $this->actingAs($admin)
            ->putJson(route('admin.page-builder.pages.update', $pageId), [
                'layout' => [
                    [
                        'id' => 'b1',
                        'type' => 'hero_block',
                        'props' => [
                            'heading' => 'Hello',
                            'subheading' => 'World',
                            'button_label' => '',
                            'button_link' => '#',
                        ],
                    ],
                ],
            ])
            ->assertOk();

        $this->actingAs($admin)
            ->postJson(route('admin.page-builder.pages.publish', $pageId))
            ->assertOk()
            ->assertJsonPath('page.is_published', true);

        $this->actingAs($admin)
            ->get(route('admin.page-preview', $pageId))
            ->assertOk()
            ->assertSee('Hello', false);

        $this->actingAs($admin)
            ->deleteJson(route('admin.page-builder.pages.destroy', $pageId))
            ->assertOk();

        $this->assertNull(AdminPage::query()->find($pageId));
    }

    public function test_non_super_admin_cannot_access_workspace_api(): void
    {
        $operator = User::factory()->create(['role' => UserRole::Operator]);

        $this->actingAs($operator)
            ->getJson(route('admin.page-builder.pages'))
            ->assertForbidden();
    }
}
