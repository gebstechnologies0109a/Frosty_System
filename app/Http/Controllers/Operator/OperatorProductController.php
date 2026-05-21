<?php

namespace App\Http\Controllers\Operator;

use App\Enums\PriceRegion;
use App\Http\Controllers\Controller;
use App\Models\Distributor;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OperatorProductController extends Controller
{
    public function search(Request $request): JsonResponse
    {
        $q = trim($request->string('q')->toString());

        if (strlen($q) < 2) {
            return response()->json([]);
        }

        $region = $this->priceRegionForSearch($request);

        $products = Product::query()
            ->active()
            ->with('prices')
            ->where('name', 'like', '%'.$q.'%')
            ->orderBy('name')
            ->limit(25)
            ->get();

        return response()->json(
            $products->map(function (Product $product) use ($region) {
                $price = $product->priceForRegion($region);
                $points = $product->points;

                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'price' => $price,
                    'points' => $points,
                    'category' => $product->category->value,
                    'text' => sprintf(
                        '%s — ₱%s (%d pt%s)',
                        $product->name,
                        number_format($price, 2),
                        $points,
                        $points === 1 ? '' : 's',
                    ),
                ];
            })->values()->all(),
        );
    }

    private function priceRegionForSearch(Request $request): PriceRegion
    {
        if ($request->filled('distributor_id')) {
            $distributor = Distributor::query()->find($request->integer('distributor_id'));

            if ($distributor) {
                return $distributor->operatorPriceRegion();
            }
        }

        return $request->user()->priceRegion();
    }
}
