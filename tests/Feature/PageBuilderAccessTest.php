<?php

namespace Tests\Feature;

use App\Enums\AdminPageStatus;
use App\Enums\UserRole;
use App\Models\AdminPage;
use App\Models\User;
use App\Services\AdminPageRenderer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PageBuilderAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_access_page_builder_routes(): void
    {
        $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);
        $page = AdminPage::query()->create([
            'title' => 'Sample',
            'slug' => 'sample-page',
            'status' => AdminPageStatus::Published,
            'layout_json' => AdminPageRenderer::defaultLayout(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.page-builder.index'))
            ->assertOk();

        $this->actingAs($admin)
            ->get(route('admin.page-builder.manage'))
            ->assertOk();

        $this->actingAs($admin)
            ->getJson(route('admin.page-builder.pages'))
            ->assertOk();

        $this->actingAs($admin)
            ->get(route('admin.page-preview', $page))
            ->assertOk();
    }

    #[DataProvider('nonSuperAdminRolesProvider')]
    public function test_only_super_admin_can_use_page_builder(UserRole $role): void
    {
        $user = User::factory()->create(['role' => $role]);
        $page = AdminPage::query()->create([
            'title' => 'Sample',
            'slug' => 'sample-page',
            'status' => AdminPageStatus::Published,
            'layout_json' => AdminPageRenderer::defaultLayout(),
        ]);

        $this->actingAs($user)
            ->get(route('admin.page-builder.index'))
            ->assertForbidden();

        $this->actingAs($user)
            ->get(route('admin.page-builder.manage'))
            ->assertForbidden();

        $this->actingAs($user)
            ->getJson(route('admin.page-builder.pages'))
            ->assertForbidden();

        $this->actingAs($user)
            ->postJson(route('admin.page-builder.pages.store'), [
                'name' => 'Blocked',
                'slug' => 'blocked-page',
            ])
            ->assertForbidden();

        $this->actingAs($user)
            ->get(route('admin.page-preview', $page))
            ->assertForbidden();
    }

    public function test_admin_nav_hides_page_builder_for_non_super_admin(): void
    {
        $finance = User::factory()->create(['role' => UserRole::FinanceAdmin]);

        $this->actingAs($finance)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertDontSee('Page Builder', false);
    }

    public function test_admin_nav_shows_page_builder_for_super_admin(): void
    {
        $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Page Builder', false);
    }

    /** @return array<string, array{0: UserRole}> */
    public static function nonSuperAdminRolesProvider(): array
    {
        return [
            'purchasing_admin' => [UserRole::PurchasingAdmin],
            'finance_admin' => [UserRole::FinanceAdmin],
            'it_admin' => [UserRole::ItAdmin],
            'operator' => [UserRole::Operator],
            'distributor' => [UserRole::Distributor],
        ];
    }
}
