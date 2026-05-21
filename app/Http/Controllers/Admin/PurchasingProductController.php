<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PriceRegion;
use App\Enums\ProductCategory;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\ProductCatalogExportService;
use App\Services\ProductCatalogFilter;
use App\Services\ProductCatalogImportService;
use App\Support\ProductInventoryService;
use App\Support\ProductRegionalPricing;
use App\Support\StockMovementLogger;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PurchasingProductController extends Controller
{
    /** @var list<string> */
    private const CATEGORY_ORDER = [
        'softserve',
        'yogurt',
        'syrup',
        'dip',
        'cone',
        'beverage',
        'coffee',
        'supply',
        'sparepart',
        'ramen',
    ];

    public function index(Request $request): View
    {
        $filters = $this->validatedFilters($request);

        return view('admin.purchasing.products.index', [
            'categories' => $this->groupedProducts($request, $filters),
            'categoryLabels' => $this->categoryLabels(),
            'productCategories' => ProductCategory::cases(),
            'canBulkManage' => auth()->user()?->role === UserRole::PurchasingAdmin,
            'filters' => $filters,
            'hasActiveFilters' => $this->hasActiveFilters($filters),
        ]);
    }

    public function bulkUpdate(Request $request): RedirectResponse
    {
        $this->ensurePurchasingAdmin($request);

        $validated = $request->validate([
            'product_ids' => ['required', 'array', 'min:1'],
            'product_ids.*' => ['integer', 'exists:products,id'],
            'category' => ['nullable', Rule::in(ProductCategory::values())],
            'points' => ['nullable', 'integer', 'min:0'],
            'status' => ['nullable', 'in:active,inactive'],
            'price_luzon' => ['nullable', 'numeric', 'min:0'],
            'price_davao' => ['nullable', 'numeric', 'min:0'],
            'price_tacloban' => ['nullable', 'numeric', 'min:0'],
        ]);

        if (! $this->hasBulkEditFields($validated)) {
            return back()->withErrors(['bulk' => 'Fill at least one field to update (category, points, status, or a regional price).']);
        }

        $updated = 0;

        DB::transaction(function () use ($validated, &$updated) {
            $products = Product::query()->with('prices')->whereIn('id', $validated['product_ids'])->get();

            foreach ($products as $product) {
                $attrs = [];

                if (! empty($validated['category'])) {
                    $attrs['category'] = $validated['category'];
                    $attrs['points'] = $this->pointsForCategory($validated['category']);
                } elseif (isset($validated['points']) && $validated['points'] !== '' && $validated['points'] !== null) {
                    if ($product->category !== ProductCategory::Softserve) {
                        $attrs['points'] = (int) $validated['points'];
                    }
                }

                if (! empty($validated['status'])) {
                    $attrs['status'] = $validated['status'];
                }

                if ($attrs !== []) {
                    $product->update($attrs);
                }

                $priceOverrides = $this->priceOverridesFromValidated($validated);
                if ($priceOverrides !== []) {
                    ProductRegionalPricing::applyPartial($product, $priceOverrides);
                }

                $updated++;
            }
        });

        return $this->bulkSuccessRedirect("Updated {$updated} product(s).");
    }

    public function bulkDelete(Request $request): RedirectResponse
    {
        $this->ensurePurchasingAdmin($request);

        $validated = $request->validate([
            'product_ids' => ['required', 'array', 'min:1'],
            'product_ids.*' => ['integer', 'exists:products,id'],
        ]);

        $deleted = 0;
        $skippedSoftserve = 0;
        $blockedOrders = 0;

        $user = $request->user();

        DB::transaction(function () use ($validated, $user, &$deleted, &$skippedSoftserve, &$blockedOrders) {
            $products = Product::query()->with('inventory')->whereIn('id', $validated['product_ids'])->get();

            foreach ($products as $product) {
                if ($product->category === ProductCategory::Softserve) {
                    $skippedSoftserve++;
                    continue;
                }

                if ($product->orderItems()->exists()) {
                    $blockedOrders++;
                    continue;
                }

                $stockBefore = $product->stockLevel();
                StockMovementLogger::logProductDeleted($product, $user, $stockBefore);
                $product->delete();
                $deleted++;
            }
        });

        $message = "Deleted {$deleted} product(s).";
        if ($skippedSoftserve > 0) {
            $message .= " {$skippedSoftserve} softserve product(s) cannot be deleted.";
        }
        if ($blockedOrders > 0) {
            $message .= " {$blockedOrders} product(s) have orders and were not deleted.";
        }

        return $this->bulkSuccessRedirect($message);
    }

    public function bulkPriceUpdate(Request $request): RedirectResponse
    {
        $this->ensurePurchasingAdmin($request);

        $validated = $request->validate([
            'product_ids' => ['required', 'array', 'min:1'],
            'product_ids.*' => ['integer', 'exists:products,id'],
            'price_luzon' => ['nullable', 'numeric', 'min:0'],
            'price_davao' => ['nullable', 'numeric', 'min:0'],
            'price_tacloban' => ['nullable', 'numeric', 'min:0'],
            'price_percent' => ['nullable', 'numeric'],
        ]);

        $hasManual = $this->priceOverridesFromValidated($validated) !== [];
        $hasPercent = isset($validated['price_percent']) && $validated['price_percent'] !== '' && $validated['price_percent'] !== null;

        if (! $hasManual && ! $hasPercent) {
            return back()->withErrors(['bulk' => 'Enter at least one regional price or a percentage change.']);
        }

        $percent = $hasPercent ? (float) $validated['price_percent'] : null;
        $overrides = $this->priceOverridesFromValidated($validated);
        $updated = 0;

        DB::transaction(function () use ($validated, $percent, $overrides, &$updated) {
            $products = Product::query()->with('prices')->whereIn('id', $validated['product_ids'])->get();

            foreach ($products as $product) {
                ProductRegionalPricing::applyPartial($product, $overrides, $percent);
                $updated++;
            }
        });

        return $this->bulkSuccessRedirect("Updated prices for {$updated} product(s).");
    }

    public function bulkCategoryUpdate(Request $request): RedirectResponse
    {
        $this->ensurePurchasingAdmin($request);

        $validated = $request->validate([
            'product_ids' => ['required', 'array', 'min:1'],
            'product_ids.*' => ['integer', 'exists:products,id'],
            'category' => ['required', Rule::in(ProductCategory::values())],
        ]);

        $updated = 0;

        DB::transaction(function () use ($validated, &$updated) {
            Product::query()
                ->whereIn('id', $validated['product_ids'])
                ->each(function (Product $product) use ($validated, &$updated) {
                    $product->update([
                        'category' => $validated['category'],
                        'points' => $this->pointsForCategory($validated['category']),
                    ]);
                    $updated++;
                });
        });

        return $this->bulkSuccessRedirect("Reassigned category for {$updated} product(s).");
    }

    public function bulkInventoryUpdate(Request $request): RedirectResponse
    {
        $this->ensurePurchasingAdmin($request);

        $validated = $request->validate([
            'product_ids' => ['required', 'array', 'min:1'],
            'product_ids.*' => ['integer', 'exists:products,id'],
            'adjustment_type' => ['required', 'in:increase,decrease,set'],
            'amount' => ['required', 'integer', 'min:0'],
        ]);

        $updated = 0;

        $user = $request->user();

        DB::transaction(function () use ($validated, $user, &$updated) {
            $products = Product::query()->whereIn('id', $validated['product_ids'])->get();

            foreach ($products as $product) {
                $result = ProductInventoryService::applyAdjustment(
                    $product,
                    $validated['adjustment_type'],
                    (int) $validated['amount'],
                );

                StockMovementLogger::logBulkAdjustment(
                    $product,
                    $user,
                    $validated['adjustment_type'],
                    (int) $validated['amount'],
                    $result['before'],
                    $result['after'],
                );

                $updated++;
            }
        });

        $label = match ($validated['adjustment_type']) {
            'increase' => 'increased',
            'decrease' => 'decreased',
            default => 'set',
        };

        return $this->bulkSuccessRedirect("Inventory {$label} for {$updated} product(s).");
    }

    public function export(Request $request, ProductCatalogExportService $export): StreamedResponse|\Illuminate\Http\Response
    {
        $this->ensurePurchasingAdmin($request);

        $request->validate([
            'format' => ['nullable', 'in:csv,xlsx'],
            'filtered' => ['nullable', 'boolean'],
        ]);

        return $export->download(
            $request,
            $request->input('format', 'csv'),
            $request->boolean('filtered'),
        );
    }

    public function importTemplate(Request $request, ProductCatalogExportService $export): StreamedResponse|\Illuminate\Http\Response
    {
        $this->ensurePurchasingAdmin($request);

        $request->validate(['format' => ['nullable', 'in:csv,xlsx']]);

        return $export->templateDownload($request->input('format', 'csv'));
    }

    public function import(Request $request, ProductCatalogImportService $import): RedirectResponse
    {
        $this->ensurePurchasingAdmin($request);

        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt,xlsx,xls', 'max:10240'],
        ]);

        $report = $import->import($request->file('file'), $request->user());

        session(['product_import_report' => $report]);

        return redirect()->route('admin.purchasing.products.import-report');
    }

    public function importReport(Request $request): View|RedirectResponse
    {
        $this->ensurePurchasingAdmin($request);

        $report = session('product_import_report');

        if (! $report) {
            return redirect()
                ->route('admin.purchasing.products.index')
                ->withErrors(['import' => 'No import report available. Run an import first.']);
        }

        return view('admin.purchasing.products.import-report', [
            'report' => $report,
        ]);
    }

    public function create(): View
    {
        return view('admin.purchasing.products.create', [
            'product' => new Product,
            'regions' => PriceRegion::cases(),
            'categories' => ProductCategory::cases(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedProduct($request);

        DB::transaction(function () use ($data, $request) {
            $product = Product::query()->create([
                'name' => $data['name'],
                'category' => $data['category'],
                'points' => $data['points'],
                'status' => $data['status'],
            ]);

            ProductRegionalPricing::sync($product, ProductRegionalPricing::pricesFromRequest($data));
            ProductInventoryService::ensure($product);
            StockMovementLogger::logProductCreated($product, $request->user());
        });

        return redirect()
            ->route('admin.purchasing.products.index')
            ->with('success', 'Product created with regional prices.');
    }

    public function edit(Product $product): View
    {
        $product->load('prices');

        return view('admin.purchasing.products.edit', [
            'product' => $product,
            'regions' => PriceRegion::cases(),
            'categories' => ProductCategory::cases(),
        ]);
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $data = $this->validatedProduct($request, $product);

        DB::transaction(function () use ($product, $data) {
            $product->update([
                'name' => $data['name'],
                'category' => $data['category'],
                'points' => $data['points'],
                'status' => $data['status'],
            ]);

            ProductRegionalPricing::sync($product, ProductRegionalPricing::pricesFromRequest($data));
        });

        return redirect()
            ->route('admin.purchasing.products.index')
            ->with('success', 'Product updated.');
    }

    public function toggleStatus(Product $product): RedirectResponse
    {
        $product->update([
            'status' => $product->isActive() ? 'inactive' : 'active',
        ]);

        return redirect()
            ->route('admin.purchasing.products.index', request()->query())
            ->with('success', 'Product status updated.');
    }

    private function ensurePurchasingAdmin(Request $request): void
    {
        abort_unless($request->user()?->role === UserRole::PurchasingAdmin, 403, 'Only Purchasing Admin can run bulk actions.');
    }

    private function bulkSuccessRedirect(string $message): RedirectResponse
    {
        return redirect()
            ->route('admin.purchasing.products.index', request()->query())
            ->with('success', $message);
    }

    private function pointsForCategory(string $category): int
    {
        return $category === ProductCategory::Softserve->value ? 2 : 0;
    }

    /** @param  array<string, mixed>  $validated */
    private function hasBulkEditFields(array $validated): bool
    {
        return ! empty($validated['category'])
            || (isset($validated['points']) && $validated['points'] !== '' && $validated['points'] !== null)
            || ! empty($validated['status'])
            || $this->priceOverridesFromValidated($validated) !== [];
    }

    /** @param  array<string, mixed>  $validated
     * @return array<string, float>
     */
    private function priceOverridesFromValidated(array $validated): array
    {
        $overrides = [];

        foreach (['luzon' => 'price_luzon', 'davao' => 'price_davao', 'tacloban' => 'price_tacloban'] as $region => $key) {
            if (isset($validated[$key]) && $validated[$key] !== '' && $validated[$key] !== null) {
                $overrides[$region] = (float) $validated[$key];
            }
        }

        return $overrides;
    }

    /** @return array<string, mixed> */
    private function validatedFilters(Request $request): array
    {
        $request->validate([
            'category' => ['nullable', Rule::in(ProductCategory::values())],
            'search' => ['nullable', 'string', 'max:255'],
            'price_luzon_min' => ['nullable', 'numeric', 'min:0'],
            'price_luzon_max' => ['nullable', 'numeric', 'min:0'],
            'price_davao_min' => ['nullable', 'numeric', 'min:0'],
            'price_davao_max' => ['nullable', 'numeric', 'min:0'],
            'price_tacloban_min' => ['nullable', 'numeric', 'min:0'],
            'price_tacloban_max' => ['nullable', 'numeric', 'min:0'],
            'status' => ['nullable', 'in:active,inactive'],
            'points' => ['nullable', 'in:0,2'],
            'inventory' => ['nullable', 'in:in_stock,out_of_stock'],
        ]);

        return [
            'category' => $request->input('category'),
            'search' => $request->input('search'),
            'price_luzon_min' => $request->input('price_luzon_min'),
            'price_luzon_max' => $request->input('price_luzon_max'),
            'price_davao_min' => $request->input('price_davao_min'),
            'price_davao_max' => $request->input('price_davao_max'),
            'price_tacloban_min' => $request->input('price_tacloban_min'),
            'price_tacloban_max' => $request->input('price_tacloban_max'),
            'status' => $request->input('status'),
            'points' => $request->input('points'),
            'inventory' => $request->input('inventory'),
        ];
    }

    /** @param  array<string, mixed>  $filters */
    private function hasActiveFilters(array $filters): bool
    {
        foreach ($filters as $value) {
            if ($value !== null && $value !== '') {
                return true;
            }
        }

        return false;
    }

    private function applyProductFilters(Builder $query, Request $request): Builder
    {
        return ProductCatalogFilter::apply($query, $request);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<string, Collection<int, Product>>
     */
    private function groupedProducts(Request $request, array $filters): Collection
    {
        $query = Product::query()->with(['prices', 'inventory']);
        $this->applyProductFilters($query, $request);
        $products = $query->get();

        $byCategory = $products
            ->groupBy(fn (Product $product) => $product->category->value)
            ->map(fn ($group) => $group->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)->values());

        $categoryOrder = ! empty($filters['category'])
            ? [$filters['category']]
            : self::CATEGORY_ORDER;

        $hideEmpty = $this->hasActiveFilters($filters);

        $categories = collect();

        foreach ($categoryOrder as $category) {
            $group = $byCategory->get($category, collect());
            if ($hideEmpty && $group->isEmpty()) {
                continue;
            }
            $categories->put($category, $group);
        }

        foreach ($byCategory as $category => $group) {
            if (! $categories->has($category)) {
                $categories->put($category, $group);
            }
        }

        return $categories;
    }

    /** @return array<string, string> */
    private function categoryLabels(): array
    {
        $labels = [];
        foreach (self::CATEGORY_ORDER as $category) {
            $labels[$category] = ProductCategory::tryFrom($category)?->label() ?? ucfirst($category);
        }

        return $labels;
    }

    /** @return array<string, mixed> */
    private function validatedProduct(Request $request, ?Product $product = null): array
    {
        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('products', 'name')->ignore($product?->id),
            ],
            'category' => ['required', Rule::in(ProductCategory::values())],
            'price_luzon' => ['required', 'numeric', 'min:0'],
            'price_davao' => ['required', 'numeric', 'min:0'],
            'price_tacloban' => ['required', 'numeric', 'min:0'],
            'points' => ['required', 'integer', 'min:0'],
            'status' => ['required', 'in:active,inactive'],
        ]);

        if ($data['category'] === ProductCategory::Softserve->value && (int) $data['points'] === 0) {
            $data['points'] = 2;
        }

        if ($data['category'] !== ProductCategory::Softserve->value) {
            $data['points'] = 0;
        }

        return $data;
    }
}
