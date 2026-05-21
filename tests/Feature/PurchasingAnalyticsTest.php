<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchasingAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_purchasing_analytics_service_builds(): void
    {
        $data = app(\App\Services\PurchasingAnalyticsService::class)->build();

        $this->assertArrayHasKey('recentStockMovements', $data);
        $this->assertArrayHasKey('summary', $data);
    }

    public function test_purchasing_analytics_page_loads_for_super_admin(): void
    {
        $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);

        $this->actingAs($admin)
            ->get(route('admin.purchasing.analytics'))
            ->assertOk()
            ->assertSee('Purchasing Analytics');
    }

    public function test_purchasing_analytics_page_loads_for_purchasing_admin(): void
    {
        $user = User::factory()->create(['role' => UserRole::PurchasingAdmin]);

        $this->actingAs($user)
            ->get(route('admin.purchasing.analytics'))
            ->assertOk()
            ->assertSee('Purchasing Analytics');
    }
}
