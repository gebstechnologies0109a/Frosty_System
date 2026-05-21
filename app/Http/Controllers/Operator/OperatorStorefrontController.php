<?php

namespace App\Http\Controllers\Operator;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\OperatorProductsForSaleService;
use Illuminate\View\View;

class OperatorStorefrontController extends Controller
{
    public function show(int $operatorId, OperatorProductsForSaleService $store): View
    {
        $operator = User::query()
            ->where('id', $operatorId)
            ->where('role', UserRole::Operator)
            ->firstOrFail();

        $menu = $store->publicMenu($operator);

        return view('store.menu', [
            'operator' => $operator,
            'menu' => $menu,
        ]);
    }
}
