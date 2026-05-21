<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

final class ProductCatalogFilter
{
    public static function apply(Builder $query, Request $request): Builder
    {
        if ($category = $request->input('category')) {
            $query->where('category', $category);
        }

        if ($search = trim((string) $request->input('search', ''))) {
            $query->where('name', 'like', '%'.$search.'%');
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($request->filled('points')) {
            $query->where('points', (int) $request->input('points'));
        }

        if ($inventory = $request->input('inventory')) {
            if ($inventory === 'in_stock') {
                $query->whereHas('inventory', fn (Builder $q) => $q->where('stock', '>', 0));
            } elseif ($inventory === 'out_of_stock') {
                $query->where(function (Builder $q) {
                    $q->whereDoesntHave('inventory')
                        ->orWhereHas('inventory', fn (Builder $iq) => $iq->where('stock', 0));
                });
            }
        }

        foreach (['luzon', 'davao', 'tacloban'] as $region) {
            $min = $request->input("price_{$region}_min");
            $max = $request->input("price_{$region}_max");

            if ($min !== null && $min !== '') {
                $query->whereHas('prices', function (Builder $q) use ($region, $min) {
                    $q->where('region', $region)->where('price', '>=', (float) $min);
                });
            }

            if ($max !== null && $max !== '') {
                $query->whereHas('prices', function (Builder $q) use ($region, $max) {
                    $q->where('region', $region)->where('price', '<=', (float) $max);
                });
            }
        }

        return $query;
    }

    public static function productsQuery(Request $request, bool $applyFilters): Builder
    {
        $query = Product::query()->with(['prices', 'inventory'])->orderBy('name');

        if ($applyFilters) {
            self::apply($query, $request);
        }

        return $query;
    }
}
