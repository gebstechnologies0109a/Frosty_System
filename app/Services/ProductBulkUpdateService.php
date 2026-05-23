<?php

namespace App\Services;

use App\Enums\ProductCategory;
use App\Models\Product;
use App\Support\ProductRegionalPricing;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

final class ProductBulkUpdateService
{
    /** @return array<string, mixed> */
    public static function validationRules(): array
    {
        return [
            'product_ids' => ['required', 'array', 'min:1'],
            'product_ids.*' => ['integer', 'exists:products,id'],
            'category' => ['nullable', Rule::in(ProductCategory::values())],
            'points' => ['nullable', 'integer', 'min:0'],
            'status' => ['nullable', 'in:active,inactive'],
            'price_luzon' => ['nullable', 'numeric', 'min:0'],
            'price_davao' => ['nullable', 'numeric', 'min:0'],
            'price_tacloban' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    /** @param  array<string, mixed>  $validated */
    public function hasBulkEditFields(array $validated): bool
    {
        return ! empty($validated['category'])
            || (isset($validated['points']) && $validated['points'] !== '' && $validated['points'] !== null)
            || ! empty($validated['status'])
            || $this->priceOverridesFromValidated($validated) !== [];
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    public function apply(array $validated): int
    {
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

        return $updated;
    }

    public function pointsForCategory(string $category): int
    {
        return $category === ProductCategory::Softserve->value ? 2 : 0;
    }

    /** @param  array<string, mixed>  $validated
     * @return array<string, float>
     */
    public function priceOverridesFromValidated(array $validated): array
    {
        $overrides = [];

        foreach (['luzon' => 'price_luzon', 'davao' => 'price_davao', 'tacloban' => 'price_tacloban'] as $region => $key) {
            if (isset($validated[$key]) && $validated[$key] !== '' && $validated[$key] !== null) {
                $overrides[$region] = (float) $validated[$key];
            }
        }

        return $overrides;
    }
}
