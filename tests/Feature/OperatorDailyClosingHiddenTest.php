<?php

namespace Tests\Feature;

use App\Enums\PosDailyClosingStatus;
use App\Enums\UserRole;
use App\Models\Distributor;
use App\Models\PosDailyClosing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OperatorDailyClosingHiddenTest extends TestCase
{
    use RefreshDatabase;

    private function operator(): User
    {
        $main = Distributor::query()->create(['name' => 'Main', 'is_main' => true]);

        return User::factory()->create([
            'role' => UserRole::Operator,
            'distributor_id' => $main->id,
        ]);
    }

    public function test_operator_dashboard_hides_daily_closing(): void
    {
        $operator = $this->operator();

        $this->actingAs($operator)
            ->get(route('operator.dashboard'))
            ->assertOk()
            ->assertDontSee('Daily closing', false)
            ->assertDontSee('Submit closing', false)
            ->assertDontSee('dailyClosingModal', false);
    }

    public function test_operator_pos_hides_daily_closing_ui(): void
    {
        $operator = $this->operator();

        $this->actingAs($operator)
            ->get(route('operator.pos.index'))
            ->assertOk()
            ->assertDontSee('Daily Closing', false)
            ->assertDontSee('Closing status', false)
            ->assertDontSee('dailyClosingModal', false);
    }

    public function test_operator_cannot_submit_daily_closing(): void
    {
        $operator = $this->operator();

        $this->actingAs($operator)
            ->postJson('/operator/pos/daily-closing', [
                'actual_cash' => 1000,
                'notes' => 'test',
            ])
            ->assertNotFound();
    }

    public function test_admin_pos_closings_index_still_works(): void
    {
        $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);
        $operator = $this->operator();

        PosDailyClosing::query()->create([
            'operator_id' => $operator->id,
            'closing_date' => now()->toDateString(),
            'total_sales' => 500,
            'total_cogs' => 200,
            'gross_profit' => 300,
            'gross_margin_percent' => 60,
            'order_count' => 3,
            'expected_cash' => 500,
            'actual_cash' => 495,
            'variance' => -5,
            'status' => PosDailyClosingStatus::Pending,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.pos.daily-closings.index'))
            ->assertOk()
            ->assertSee('POS Daily Closings');
    }

    public function test_admin_can_approve_daily_closing(): void
    {
        $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);
        $operator = $this->operator();

        $closing = PosDailyClosing::query()->create([
            'operator_id' => $operator->id,
            'closing_date' => now()->toDateString(),
            'total_sales' => 500,
            'total_cogs' => 200,
            'gross_profit' => 300,
            'gross_margin_percent' => 60,
            'order_count' => 3,
            'expected_cash' => 500,
            'actual_cash' => 500,
            'variance' => 0,
            'status' => PosDailyClosingStatus::Pending,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.pos.daily-closing.approve', $closing))
            ->assertRedirect();

        $this->assertSame(PosDailyClosingStatus::Approved, $closing->fresh()->status);
    }
}
