<?php

namespace Tests\Feature;

use App\Enums\OrderSource;
use App\Enums\OrderStatus;
use App\Enums\PriceRegion;
use App\Enums\ProductCategory;
use App\Enums\UserRole;
use App\Models\Distributor;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\User;
use App\Services\OrderPaymentProofService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OrderPaymentProofTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        config(['frosty.require_payment_proof' => true]);
    }

    private function operatorWithProduct(): array
    {
        $main = Distributor::query()->create(['name' => 'Main', 'is_main' => true]);
        $operator = User::factory()->create([
            'role' => UserRole::Operator,
            'distributor_id' => $main->id,
        ]);
        $product = Product::query()->create([
            'name' => 'Supply Item',
            'category' => ProductCategory::Supply,
            'points' => 0,
            'status' => 'active',
        ]);

        foreach (PriceRegion::cases() as $region) {
            ProductPrice::query()->create([
                'product_id' => $product->id,
                'region' => $region,
                'price' => 100,
            ]);
        }

        return [$operator, $main, $product];
    }

    public function test_operator_can_submit_order_with_jpg_proof(): void
    {
        [$operator, $main, $product] = $this->operatorWithProduct();

        $this->actingAs($operator)
            ->post(route('operator.orders.store'), [
                'distributor_id' => $main->id,
                'items' => [['product_id' => $product->id, 'qty' => 2]],
                'payment_proof' => UploadedFile::fake()->image('receipt.jpg'),
            ])
            ->assertRedirect(route('operator.orders.index'));

        $order = Order::query()->latest('id')->first();
        $this->assertNotNull($order->payment_proof_path);
        Storage::disk('public')->assertExists($order->payment_proof_path);
    }

    public function test_operator_can_submit_order_with_png_proof(): void
    {
        [$operator, $main, $product] = $this->operatorWithProduct();

        $this->actingAs($operator)
            ->post(route('operator.orders.store'), [
                'distributor_id' => $main->id,
                'items' => [['product_id' => $product->id, 'qty' => 1]],
                'payment_proof' => UploadedFile::fake()->image('receipt.png'),
            ])
            ->assertRedirect();

        $this->assertNotNull(Order::query()->latest('id')->first()->payment_proof_path);
    }

    public function test_operator_can_submit_order_with_pdf_proof(): void
    {
        [$operator, $main, $product] = $this->operatorWithProduct();

        $this->actingAs($operator)
            ->post(route('operator.orders.store'), [
                'distributor_id' => $main->id,
                'items' => [['product_id' => $product->id, 'qty' => 1]],
                'payment_proof' => UploadedFile::fake()->create('receipt.pdf', 100, 'application/pdf'),
            ])
            ->assertRedirect();

        $this->assertStringEndsWith('.pdf', Order::query()->latest('id')->first()->payment_proof_path);
    }

    public function test_operator_can_submit_without_proof(): void
    {
        [$operator, $main, $product] = $this->operatorWithProduct();

        $this->actingAs($operator)
            ->post(route('operator.orders.store'), [
                'distributor_id' => $main->id,
                'items' => [['product_id' => $product->id, 'qty' => 1]],
            ])
            ->assertRedirect();

        $this->assertNull(Order::query()->latest('id')->first()->payment_proof_path);
    }

    public function test_operator_rejects_invalid_proof_type(): void
    {
        [$operator, $main, $product] = $this->operatorWithProduct();

        $this->actingAs($operator)
            ->post(route('operator.orders.store'), [
                'distributor_id' => $main->id,
                'items' => [['product_id' => $product->id, 'qty' => 1]],
                'payment_proof' => UploadedFile::fake()->create('virus.exe', 10, 'application/octet-stream'),
            ])
            ->assertSessionHasErrors('payment_proof');
    }

    public function test_admin_sees_proof_on_order_details(): void
    {
        [$operator, $main, $product] = $this->operatorWithProduct();
        $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);

        $path = (new OrderPaymentProofService)->store(UploadedFile::fake()->image('proof.jpg'));
        $order = Order::query()->create([
            'user_id' => $operator->id,
            'distributor_id' => $main->id,
            'status' => OrderStatus::Pending,
            'total_amount' => 100,
            'total_points' => 0,
            'source' => OrderSource::Operator,
            'price_region' => PriceRegion::Luzon,
            'payment_proof_path' => $path,
        ]);

        Storage::disk('public')->put($path, 'fake-image');

        $this->actingAs($admin)
            ->get(route('admin.orders.show', $order))
            ->assertOk()
            ->assertSee('Proof of payment')
            ->assertSee('View full image')
            ->assertSee('Download proof');
    }

    public function test_admin_sees_missing_proof_message(): void
    {
        [$operator, $main] = $this->operatorWithProduct();
        $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);

        $order = Order::query()->create([
            'user_id' => $operator->id,
            'distributor_id' => $main->id,
            'status' => OrderStatus::Pending,
            'total_amount' => 50,
            'total_points' => 0,
            'source' => OrderSource::Operator,
            'price_region' => PriceRegion::Luzon,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.orders.show', $order))
            ->assertOk()
            ->assertSee('No proof of payment submitted.');
    }

    public function test_admin_can_download_proof(): void
    {
        [$operator, $main] = $this->operatorWithProduct();
        $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);

        $path = (new OrderPaymentProofService)->store(UploadedFile::fake()->image('proof.jpg'));
        Storage::disk('public')->put($path, 'image-bytes');

        $order = Order::query()->create([
            'user_id' => $operator->id,
            'distributor_id' => $main->id,
            'status' => OrderStatus::Pending,
            'total_amount' => 50,
            'total_points' => 0,
            'source' => OrderSource::Operator,
            'price_region' => PriceRegion::Luzon,
            'payment_proof_path' => $path,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.orders.payment-proof', $order))
            ->assertOk();
    }

    public function test_admin_can_approve_order_with_proof(): void
    {
        [$operator, $main] = $this->operatorWithProduct();
        $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);

        $order = Order::query()->create([
            'user_id' => $operator->id,
            'distributor_id' => $main->id,
            'status' => OrderStatus::Pending,
            'total_amount' => 50,
            'total_points' => 0,
            'source' => OrderSource::Operator,
            'price_region' => PriceRegion::Luzon,
            'payment_proof_path' => 'order_payments/test.jpg',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.orders.approve', $order))
            ->assertRedirect();

        $this->assertSame(OrderStatus::Approved, $order->fresh()->status);
    }

    public function test_admin_cannot_approve_without_proof(): void
    {
        [$operator, $main] = $this->operatorWithProduct();
        $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);

        $order = Order::query()->create([
            'user_id' => $operator->id,
            'distributor_id' => $main->id,
            'status' => OrderStatus::Pending,
            'total_amount' => 50,
            'total_points' => 0,
            'source' => OrderSource::Operator,
            'price_region' => PriceRegion::Luzon,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.orders.approve', $order))
            ->assertRedirect()
            ->assertSessionHas('error', 'Cannot approve order. Proof of payment is missing.');

        $this->assertSame(OrderStatus::Pending, $order->fresh()->status);
    }

    /** @return array{operator: User, distributor: Distributor, distributorUser: User} */
    private function operatorOrderToDistributor(): array
    {
        Distributor::query()->create(['name' => 'Main', 'is_main' => true]);
        $distributorUser = User::factory()->create(['role' => UserRole::Distributor]);
        $distributor = Distributor::query()->create([
            'name' => 'General Santos City',
            'user_id' => $distributorUser->id,
            'is_main' => false,
        ]);
        $operator = User::factory()->create([
            'role' => UserRole::Operator,
            'distributor_id' => $distributor->id,
        ]);

        return ['operator' => $operator, 'distributor' => $distributor, 'distributorUser' => $distributorUser];
    }

    public function test_distributor_cannot_approve_without_proof_returns_error_not_server_error(): void
    {
        ['operator' => $operator, 'distributor' => $distributor, 'distributorUser' => $distributorUser] = $this->operatorOrderToDistributor();

        $order = Order::query()->create([
            'user_id' => $operator->id,
            'distributor_id' => $distributor->id,
            'status' => OrderStatus::Pending,
            'total_amount' => 50,
            'total_points' => 2,
            'source' => OrderSource::Operator,
            'price_region' => PriceRegion::Davao,
        ]);

        $this->actingAs($distributorUser)
            ->post(route('distributor.orders.approve', $order))
            ->assertRedirect()
            ->assertSessionHas('error', 'Cannot approve order. Proof of payment is missing.');

        $this->assertSame(OrderStatus::Pending, $order->fresh()->status);
    }

    public function test_distributor_can_approve_order_with_proof(): void
    {
        ['operator' => $operator, 'distributor' => $distributor, 'distributorUser' => $distributorUser] = $this->operatorOrderToDistributor();

        $order = Order::query()->create([
            'user_id' => $operator->id,
            'distributor_id' => $distributor->id,
            'status' => OrderStatus::Pending,
            'total_amount' => 50,
            'total_points' => 2,
            'source' => OrderSource::Operator,
            'price_region' => PriceRegion::Davao,
            'payment_proof_path' => 'order_payments/receipt.jpg',
        ]);

        $this->actingAs($distributorUser)
            ->post(route('distributor.orders.approve', $order))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame(OrderStatus::Approved, $order->fresh()->status);
    }

    public function test_distributor_orders_index_hides_approve_when_proof_missing(): void
    {
        ['operator' => $operator, 'distributor' => $distributor, 'distributorUser' => $distributorUser] = $this->operatorOrderToDistributor();

        Order::query()->create([
            'user_id' => $operator->id,
            'distributor_id' => $distributor->id,
            'status' => OrderStatus::Pending,
            'total_amount' => 50,
            'total_points' => 2,
            'source' => OrderSource::Operator,
            'price_region' => PriceRegion::Davao,
        ]);

        $this->actingAs($distributorUser)
            ->get(route('distributor.orders.index'))
            ->assertOk()
            ->assertSee('Awaiting payment proof', false)
            ->assertDontSee('btn btn-sm btn-success">Approve', false);
    }
}
