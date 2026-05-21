<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\OperatorProduct;
use App\Models\User;
use App\Services\OperatorProductsForSaleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Support\ListPage;
use Illuminate\View\View;

class AdminOperatorProductController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless(auth()->user()?->role === UserRole::SuperAdmin, 403);

        $query = OperatorProduct::query()->with('operator:id,name,email')->orderBy('product_name');

        if ($request->filled('operator_id')) {
            $query->where('operator_id', $request->input('operator_id'));
        }

        return view('admin.operator-products.index', [
            'products' => $query->paginate(ListPage::perPage($request, 20))->withQueryString(),
            'operators' => User::query()->where('role', UserRole::Operator)->orderBy('name')->get(['id', 'name']),
            'filters' => $request->only(['operator_id']),
        ]);
    }

    public function update(Request $request, OperatorProduct $operatorProduct, OperatorProductsForSaleService $store): RedirectResponse
    {
        abort_unless(auth()->user()?->role === UserRole::SuperAdmin, 403);

        $data = $request->validate([
            'product_name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'price' => ['required', 'numeric', 'min:0'],
            'cost' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', 'in:active,inactive'],
        ]);

        $store->update(
            $operatorProduct,
            $data['product_name'],
            (float) $data['price'],
            $data['status'],
            $data['description'] ?? null,
            isset($data['cost']) ? (float) $data['cost'] : null,
        );

        return back()->with('success', 'Operator product updated.');
    }
}
