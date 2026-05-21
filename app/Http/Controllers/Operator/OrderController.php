<?php

namespace App\Http\Controllers\Operator;

use App\Enums\OrderStatus;
use App\Enums\PriceRegion;
use App\Http\Controllers\Controller;
use App\Models\Distributor;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\User;
use App\Services\OperatorOrderService;
use App\Services\OrderEngine;
use App\Services\OrderPaymentProofService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use App\Support\ListPage;
use Illuminate\View\View;
use RuntimeException;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        return view('operator.orders.history', [
            'orders' => Auth::user()->orders()->with(['items.product', 'distributor'])->latest()
                ->paginate(ListPage::perPage($request, 20))
                ->withQueryString(),
        ]);
    }

    public function show(Order $order, OperatorOrderService $orders, OrderPaymentProofService $proofs): View
    {
        $orders->authorizeOwner($order, Auth::user());

        $order->load(['items.product', 'distributor', 'approver']);

        return view('operator.orders.show', [
            'order' => $order,
            'paymentProofUrl' => $proofs->url($order->payment_proof_path),
            'timeline' => $orders->statusTimeline($order),
            'activity' => $orders->orderActivity($order),
            'pricingRegionLabel' => $order->distributor?->pricingRegion()->label()
                ?? $order->price_region?->label(),
        ]);
    }

    public function edit(Order $order, OperatorOrderService $orders): View
    {
        $orders->authorizeOwner($order, Auth::user());

        if (! in_array($order->status, [OrderStatus::Pending, OrderStatus::Rejected], true)) {
            abort(403, 'Only pending or rejected orders can be edited.');
        }

        $order->load(['items.product', 'distributor']);

        $initialLines = $order->items->map(fn ($item) => [
            'product_id' => $item->product_id,
            'name' => $item->product?->name ?? 'Product',
            'qty' => $item->qty,
            'price' => (float) $item->price,
            'points' => $item->points,
            'category' => $item->product?->category?->value ?? '',
        ])->values()->all();

        return view('operator.orders.edit', [
            'order' => $order,
            'distributors' => Distributor::query()->orderByDesc('is_main')->orderBy('name')->get(),
            'productSearchUrl' => route('operator.products.search'),
            'initialLines' => $initialLines,
            'distributorRegionLabels' => Distributor::query()->orderByDesc('is_main')->orderBy('name')->get()
                ->mapWithKeys(fn ($d) => [$d->id => $d->pricingRegion()->label()])
                ->all(),
        ]);
    }

    public function update(
        Request $request,
        Order $order,
        OperatorOrderService $orders,
        OrderPaymentProofService $proofs,
    ): RedirectResponse {
        $validated = $request->validate([
            'distributor_id' => ['required', 'exists:distributors,id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.qty' => ['required', 'integer', 'min:1'],
            'payment_proof' => ['nullable', 'file', 'mimes:jpg,jpeg,png,heic,pdf', 'max:5120'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        /** @var User $user */
        $user = Auth::user();
        $distributor = Distributor::query()->findOrFail($validated['distributor_id']);
        $items = $this->validateOrderItems($validated['items'], $distributor->operatorPriceRegion());

        $proofPath = null;
        if ($request->hasFile('payment_proof')) {
            $proofPath = $proofs->store($request->file('payment_proof'));
        }

        $resubmit = $order->status === OrderStatus::Rejected;

        try {
            $orders->saveEditableOrder(
                $order,
                $user,
                $items,
                (int) $validated['distributor_id'],
                $proofPath,
                $validated['notes'] ?? null,
                resubmitIfRejected: $resubmit,
            );
        } catch (RuntimeException $e) {
            return redirect()
                ->route('operator.orders.edit', $order)
                ->with('error', $e->getMessage())
                ->withInput();
        }

        $message = $resubmit
            ? 'Order updated and re-submitted to your distributor.'
            : 'Order changes saved.';

        return redirect()
            ->route('operator.orders.show', $order)
            ->with('success', $message);
    }

    public function uploadPaymentProof(
        Request $request,
        Order $order,
        OperatorOrderService $orders,
        OrderPaymentProofService $proofs,
    ): RedirectResponse {
        $validated = $request->validate([
            'payment_proof' => ['required', 'file', 'mimes:jpg,jpeg,png,heic,pdf', 'max:5120'],
        ]);

        try {
            $orders->uploadPaymentProof(
                $order,
                Auth::user(),
                $proofs->store($validated['payment_proof']),
            );
        } catch (RuntimeException $e) {
            return redirect()
                ->route('operator.orders.show', $order)
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('operator.orders.show', $order)
            ->with('success', 'Payment proof uploaded.');
    }

    public function resubmit(Order $order, OperatorOrderService $orders): RedirectResponse
    {
        try {
            $orders->resubmit($order, Auth::user());
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('operator.orders.show', $order)
            ->with('success', 'Order re-submitted. Your distributor has been notified.');
    }

    public function create(): View
    {
        return view('operator.orders.create', [
            'distributors' => Distributor::query()->orderByDesc('is_main')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, OrderEngine $engine, OrderPaymentProofService $proofs): RedirectResponse
    {
        $validated = $request->validate([
            'distributor_id' => ['required', 'exists:distributors,id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.qty' => ['required', 'integer', 'min:1'],
            'payment_proof' => ['nullable', 'file', 'mimes:jpg,jpeg,png,heic,pdf', 'max:5120'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        /** @var User $user */
        $user = Auth::user();
        $distributor = Distributor::query()->findOrFail($validated['distributor_id']);
        $items = $this->validateOrderItems($validated['items'], $distributor->operatorPriceRegion());

        $proofPath = null;
        if ($request->hasFile('payment_proof')) {
            $proofPath = $proofs->store($request->file('payment_proof'));
        }

        $order = $engine->create($user, $items, (int) $validated['distributor_id'], $proofPath);

        if (! empty($validated['notes'])) {
            $order->update(['notes' => $validated['notes']]);
        }

        return redirect()->route('operator.orders.index')->with('success', 'Order submitted.');
    }

    /**
     * @param  array<int, array{product_id: mixed, qty: mixed}>  $items
     * @return array<int, array{product_id: int, qty: int}>
     */
    private function validateOrderItems(array $items, PriceRegion $region): array
    {
        $validated = [];

        foreach ($items as $index => $row) {
            $product = Product::query()
                ->active()
                ->with('prices')
                ->find($row['product_id']);

            if (! $product) {
                throw ValidationException::withMessages([
                    "items.{$index}.product_id" => 'The selected product is invalid or inactive.',
                ]);
            }

            $hasRegionalPrice = $product->prices->contains(
                fn (ProductPrice $price) => $price->region === $region,
            );

            if (! $hasRegionalPrice) {
                throw ValidationException::withMessages([
                    "items.{$index}.product_id" => 'This product is not priced for your region ('.$region->label().').',
                ]);
            }

            $qty = (int) $row['qty'];
            if ($qty < 1) {
                throw ValidationException::withMessages([
                    "items.{$index}.qty" => 'Quantity must be at least 1.',
                ]);
            }

            $validated[] = [
                'product_id' => $product->id,
                'qty' => $qty,
            ];
        }

        return $validated;
    }
}
