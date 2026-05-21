<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ProductCategory;
use App\Http\Controllers\Controller;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\StockMovement;
use App\Support\ListPage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminProductController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->only(['q', 'category', 'status']);
        $query = Product::query()->with(['inventory', 'prices']);

        if (! empty($filters['q'])) {
            $q = '%'.$filters['q'].'%';
            $query->where('name', 'like', $q);
        }

        if (! empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return view('admin.products.index', [
            'products' => $query->orderBy('name')->paginate(ListPage::perPage($request, 20))->withQueryString(),
            'categories' => ProductCategory::cases(),
            'filters' => $filters,
        ]);
    }

    public function create(): RedirectResponse
    {
        return redirect()->route('admin.purchasing.products.create');
    }

    public function show(Product $product): View
    {
        $product->load(['inventory', 'prices', 'stockMovements' => fn ($q) => $q->with('user:id,name')->limit(50)]);

        return view('admin.products.show', [
            'product' => $product,
            'stockMovements' => $product->stockMovements,
            'priceHistory' => $product->prices()->orderByDesc('updated_at')->get(),
        ]);
    }

    public function edit(Product $product): RedirectResponse
    {
        return redirect()->route('admin.purchasing.products.edit', $product);
    }

    public function destroy(Product $product): RedirectResponse
    {
        if (OrderItem::query()->where('product_id', $product->id)->exists()) {
            return back()->withErrors(['delete' => 'Product is used in orders and cannot be deleted.']);
        }

        $product->delete();

        return redirect()
            ->route('admin.products.index')
            ->with('success', 'Product deleted.');
    }

    public function toggleStatus(Product $product): RedirectResponse
    {
        $product->update([
            'status' => $product->isActive() ? 'inactive' : 'active',
        ]);

        return back()->with('success', 'Product status updated.');
    }

    public function stockLogs(Request $request, Product $product): View
    {
        $movements = StockMovement::query()
            ->where('product_id', $product->id)
            ->with('user:id,name')
            ->latest('created_at')
            ->paginate(ListPage::perPage($request, 20))
            ->withQueryString();

        return view('admin.products.stock-logs', compact('product', 'movements'));
    }

    public function priceHistory(Product $product): View
    {
        $product->load('prices');

        return view('admin.products.price-history', [
            'product' => $product,
            'prices' => $product->prices,
        ]);
    }
}
