<?php

namespace Tests\Feature;

use App\Enums\DistributorPricingRegion;
use App\Enums\PriceRegion;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Distributor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DistributorPricingRegionTest extends TestCase
{
    use RefreshDatabase;

    public function test_distributor_create_and_edit_pricing_region(): void
    {
        $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);

        $this->actingAs($admin)
            ->post(route('admin.distributors.store'), [
                'name' => 'Mindanao Dist',
                'email' => 'mindanao@frosty.test',
                'password' => 'Password123!',
                'pricing_region' => DistributorPricingRegion::Mindanao->value,
            ])
            ->assertRedirect();

        $user = User::query()->where('email', 'mindanao@frosty.test')->first();
        $this->assertNotNull($user);
        $profile = $user->distributorProfile;
        $this->assertSame(DistributorPricingRegion::Mindanao, $profile->pricing_region);

        $this->actingAs($admin)
            ->put(route('admin.distributors.update', $user), [
                'name' => 'Mindanao Dist',
                'email' => 'mindanao@frosty.test',
                'status' => UserStatus::Active->value,
                'pricing_region' => DistributorPricingRegion::Luzon->value,
            ])
            ->assertRedirect();

        $this->assertSame(DistributorPricingRegion::Luzon, $profile->fresh()->pricing_region);
    }

    public function test_operator_inherits_distributor_pricing_region(): void
    {
        $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);
        $distUser = User::factory()->create(['role' => UserRole::Distributor]);
        $profile = Distributor::query()->create([
            'name' => 'Mindanao Hub',
            'user_id' => $distUser->id,
            'is_main' => false,
            'pricing_region' => DistributorPricingRegion::Mindanao->value,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.users.store'), [
                'first_name' => 'Op',
                'last_name' => 'One',
                'email' => 'op.one@frosty.test',
                'role' => UserRole::Operator->value,
                'status' => UserStatus::Active->value,
                'password' => 'Password123!',
                'distributor_id' => $profile->id,
            ]);

        $operator = User::query()->where('email', 'op.one@frosty.test')->first();
        $this->assertNotNull($operator);
        $this->assertSame(PriceRegion::Davao, $operator->region);
    }

    public function test_updating_distributor_region_syncs_assigned_operators(): void
    {
        $profile = Distributor::query()->create([
            'name' => 'Dist',
            'is_main' => false,
            'pricing_region' => DistributorPricingRegion::Luzon->value,
        ]);

        $operator = User::factory()->create([
            'role' => UserRole::Operator,
            'distributor_id' => $profile->id,
            'region' => PriceRegion::Luzon,
        ]);

        $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);
        $distUser = User::factory()->create([
            'role' => UserRole::Distributor,
        ]);
        $profile->update(['user_id' => $distUser->id]);

        $this->actingAs($admin)
            ->put(route('admin.distributors.update', $distUser), [
                'name' => $distUser->name,
                'email' => $distUser->email,
                'status' => UserStatus::Active->value,
                'pricing_region' => DistributorPricingRegion::Mindanao->value,
            ]);

        $this->assertSame(PriceRegion::Davao, $operator->fresh()->region);
    }
}
