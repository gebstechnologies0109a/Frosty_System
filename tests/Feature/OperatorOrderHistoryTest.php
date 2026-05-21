<?php

namespace Tests\Feature;

use App\Enums\DistributorPricingRegion;
use App\Enums\OrderSource;
use App\Enums\OrderStatus;
use App\Enums\PriceRegion;
use App\Enums\ProductCategory;
use App\Enums\UserRole;
use App\Models\ActivityLog;
use App\Models\Distributor;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OperatorOrderHistoryTest extends TestCase
{
    use RefreshDatabase;

    private function operatorWithDistributor(): array
    {
        $distributorUser = User::factory()->create(['role' => UserRole::Distributor]);
        $distributor = Distributor::query()->create([
            'name' => 'General Santos City',
            'user_id' => $distributorUser->id,
            'is_main' => false,
            'pricing_region' => DistributorPricingRegion::Mindanao,
        ]);
        $operator = User::factory()->create([
            'role' => UserRole::Operator,
            'distributor_id' => $distributor->id,
        ]);

        return [$operator, $distributor, $distributorUser];
    }

    private function seedProduct(): Product
    {
        $product = Product::query()->create([
            'name' => 'Vanilla Softserve 1kg',
            'category' => ProductCategory::Softserve,
            'points' => 2,
            'status' => 'active',
        ]);

        foreach (PriceRegion::cases() as $region) {
            ProductPrice::query()->create([
                'product_id' => $product->id,
                'region' => $region,
                'price' => $region === PriceRegion::Davao ? 198.50 : 228.00,
            ]);
        }

        return $product;
    }

    private function pendingOrder(User $operator, Distributor $distributor, ?string $proofPath = null): Order
    {
        $product = $this->seedProduct();

        $order = Order::query()->create([
            'user_id' => $operator->id,
            'distributor_id' => $distributor->id,
            'status' => OrderStatus::Pending,
            'total_amount' => 198.50,
            'total_points' => 2,
            'source' => OrderSource::Operator,
            'price_region' => PriceRegion::Davao,
            'payment_proof_path' => $proofPath,
            'notes' => 'Rush delivery',
        ]);

        $order->items()->create([
            'product_id' => $product->id,
            'qty' => 1,
            'price' => 198.50,
            'points' => 2,
            'line_total' => 198.50,
        ]);

        return $order->load('items.product');
    }

    public function test_order_history_shows_view_button(): void
    {
        [$operator, $distributor] = $this->operatorWithDistributor();
        $order = $this->pendingOrder($operator, $distributor);

        $this->actingAs($operator)
            ->get(route('operator.orders.index'))
            ->assertOk()
            ->assertSee('Order history', false)
            ->assertSee(route('operator.orders.show', $order->id), false)
            ->assertSee('btn btn-primary btn-sm">View', false);
    }

    public function test_show_page_displays_full_order_details(): void
    {
        [$operator, $distributor] = $this->operatorWithDistributor();
        $order = $this->pendingOrder($operator, $distributor, 'order_payments/proof.jpg');

        $this->actingAs($operator)
            ->get(route('operator.orders.show', $order))
            ->assertOk()
            ->assertSee('Vanilla Softserve 1kg', false)
            ->assertSee('₱198.50', false)
            ->assertSee('General Santos City', false)
            ->assertSee('Mindanao', false)
            ->assertSee('Rush delivery', false)
            ->assertSee('Status timeline', false)
            ->assertSee('Edit order', false);
    }

    public function test_operator_can_upload_payment_proof(): void
    {
        Storage::fake('public');
        [$operator, $distributor] = $this->operatorWithDistributor();
        $order = $this->pendingOrder($operator, $distributor);

        $this->actingAs($operator)
            ->post(route('operator.orders.payment-proof', $order), [
                'payment_proof' => UploadedFile::fake()->image('receipt.jpg'),
            ])
            ->assertRedirect(route('operator.orders.show', $order))
            ->assertSessionHas('success');

        $this->assertNotNull($order->fresh()->payment_proof_path);
        Storage::disk('public')->assertExists($order->fresh()->payment_proof_path);
    }

    public function test_edit_page_only_for_pending_orders(): void
    {
        [$operator, $distributor] = $this->operatorWithDistributor();
        $pending = $this->pendingOrder($operator, $distributor);
        $approved = $this->pendingOrder($operator, $distributor);
        $approved->update(['status' => OrderStatus::Approved, 'approved_at' => now()]);

        $this->actingAs($operator)
            ->get(route('operator.orders.edit', $pending))
            ->assertOk()
            ->assertSee('Edit order', false);

        $this->actingAs($operator)
            ->get(route('operator.orders.edit', $approved))
            ->assertForbidden();
    }

    public function test_operator_can_update_pending_order(): void
    {
        [$operator, $distributor] = $this->operatorWithDistributor();
        Distributor::query()->create(['name' => 'Main', 'is_main' => true]);
        $order = $this->pendingOrder($operator, $distributor);
        $product = $this->seedProduct();

        $this->actingAs($operator)
            ->put(route('operator.orders.update', $order), [
                'distributor_id' => $distributor->id,
                'items' => [['product_id' => $product->id, 'qty' => 3]],
                'notes' => 'Updated qty',
            ])
            ->assertRedirect(route('operator.orders.show', $order))
            ->assertSessionHas('success');

        $order->refresh();
        $this->assertSame('Updated qty', $order->notes);
        $this->assertEquals(3, $order->items->first()->qty);
        $this->assertEquals(595.50, (float) $order->total_amount);
    }

    public function test_resubmit_only_for_rejected_orders(): void
    {
        [$operator, $distributor, $distributorUser] = $this->operatorWithDistributor();
        $order = $this->pendingOrder($operator, $distributor);
        $order->update(['status' => OrderStatus::Rejected]);

        $this->actingAs($operator)
            ->post(route('operator.orders.resubmit', $order))
            ->assertRedirect(route('operator.orders.show', $order))
            ->assertSessionHas('success');

        $this->assertSame(OrderStatus::Pending, $order->fresh()->status);

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $distributorUser->id,
            'action' => 'order.resubmitted_notify',
        ]);
    }

    public function test_cannot_resubmit_pending_order(): void
    {
        [$operator, $distributor] = $this->operatorWithDistributor();
        $order = $this->pendingOrder($operator, $distributor);

        $this->actingAs($operator)
            ->post(route('operator.orders.resubmit', $order))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertSame(OrderStatus::Pending, $order->fresh()->status);
    }

    public function test_operator_cannot_view_another_operators_order(): void
    {
        [$operator, $distributor] = $this->operatorWithDistributor();
        $other = User::factory()->create(['role' => UserRole::Operator, 'distributor_id' => $distributor->id]);
        $order = $this->pendingOrder($other, $distributor);

        $this->actingAs($operator)
            ->get(route('operator.orders.show', $order))
            ->assertForbidden();
    }

    public function test_distributor_sees_resubmitted_order_as_pending(): void
    {
        [$operator, $distributor, $distributorUser] = $this->operatorWithDistributor();
        $order = $this->pendingOrder($operator, $distributor);
        $order->update(['status' => OrderStatus::Rejected]);

        $this->actingAs($operator)->post(route('operator.orders.resubmit', $order));

        $this->actingAs($distributorUser)
            ->get(route('distributor.orders.index'))
            ->assertOk()
            ->assertSee((string) $order->id, false)
            ->assertSee('pending', false);
    }
}
