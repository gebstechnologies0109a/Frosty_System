<?php

namespace App\Http\Controllers\Operator;

use App\Enums\PriceRegion;
use App\Http\Controllers\Controller;
use App\Models\Distributor;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\User;
use App\Services\OrderEngine;
use App\Services\OrderPaymentProofService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use App\Support\ListPage;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        return view('operator.orders.index', [
            'orders' => Auth::user()->orders()->with(['items.product', 'distributor'])->latest()
                ->paginate(ListPage::perPage($request, 20))
                ->withQueryString(),
        ]);
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
        ]);

        /** @var User $user */
        $user = Auth::user();
        $distributor = Distributor::query()->findOrFail($validated['distributor_id']);
        $items = $this->validateOrderItems($validated['items'], $distributor->operatorPriceRegion());

        $proofPath = null;
        if ($request->hasFile('payment_proof')) {
            $proofPath = $proofs->store($request->file('payment_proof'));
        }

        $engine->create($user, $items, (int) $validated['distributor_id'], $proofPath);

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
