<?php

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Models\OperatorProduct;
use App\Services\OperatorProductsForSaleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class OperatorProductsForSaleController extends Controller
{
    public function index(Request $request, OperatorProductsForSaleService $store): View
    {
        $data = $store->indexData(Auth::user(), $request);

        return view('operator.products_for_sale.index', [
            'operator' => Auth::user(),
            'items' => $data['items'],
            'filters' => $data['filters'],
        ]);
    }

    public function store(Request $request, OperatorProductsForSaleService $store): RedirectResponse
    {
        $data = $request->validate([
            'product_name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'price' => ['required', 'numeric', 'min:0'],
            'cost' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', 'in:active,inactive'],
            'image' => ['nullable', 'image', 'max:2048'],
        ]);

        $store->store(
            Auth::user(),
            $data['product_name'],
            (float) $data['price'],
            $data['status'],
            $data['description'] ?? null,
            isset($data['cost']) ? (float) $data['cost'] : null,
            $request->file('image'),
        );

        return back()->with('success', 'Product for sale added.');
    }

    public function update(Request $request, OperatorProduct $operatorProduct, OperatorProductsForSaleService $store): RedirectResponse
    {
        $this->authorizeOperator($operatorProduct);

        $data = $request->validate([
            'product_name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'price' => ['required', 'numeric', 'min:0'],
            'cost' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', 'in:active,inactive'],
            'image' => ['nullable', 'image', 'max:2048'],
            'remove_image' => ['nullable', 'boolean'],
        ]);

        $store->update(
            $operatorProduct,
            $data['product_name'],
            (float) $data['price'],
            $data['status'],
            $data['description'] ?? null,
            isset($data['cost']) ? (float) $data['cost'] : null,
            $request->file('image'),
            (bool) ($data['remove_image'] ?? false),
        );

        return back()->with('success', 'Product updated.');
    }

    public function toggle(OperatorProduct $operatorProduct, OperatorProductsForSaleService $store): RedirectResponse
    {
        $this->authorizeOperator($operatorProduct);
        $store->toggleStatus($operatorProduct);

        return back()->with('success', 'Product status updated.');
    }

    private function authorizeOperator(OperatorProduct $operatorProduct): void
    {
        abort_unless((int) $operatorProduct->operator_id === (int) Auth::id(), 403);
    }
}
