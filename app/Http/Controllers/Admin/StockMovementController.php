<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ProductCategory;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StockMovementController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless(
            in_array($request->user()?->role, [UserRole::PurchasingAdmin, UserRole::SuperAdmin], true),
            403,
        );

        $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'product_id' => ['nullable', 'integer', 'exists:products,id'],
            'action_type' => ['nullable', 'in:'.implode(',', StockMovement::ACTION_TYPES)],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'quantity_direction' => ['nullable', 'in:positive,negative,zero'],
            'category' => ['nullable', 'in:'.implode(',', ProductCategory::values())],
        ]);

        $query = StockMovement::query()
            ->with(['product', 'user'])
            ->latest('created_at');

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }

        if ($request->filled('product_id')) {
            $query->where('product_id', $request->input('product_id'));
        }

        if ($request->filled('action_type')) {
            $query->where('action_type', $request->input('action_type'));
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        if ($direction = $request->input('quantity_direction')) {
            match ($direction) {
                'positive' => $query->where('quantity_change', '>', 0),
                'negative' => $query->where('quantity_change', '<', 0),
                'zero' => $query->where('quantity_change', 0),
                default => null,
            };
        }

        if ($request->filled('category')) {
            $query->whereHas('product', fn ($q) => $q->where('category', $request->input('category')));
        }

        return view('admin.purchasing.stock-movements.index', [
            'movements' => $query->paginate(50)->withQueryString(),
            'filters' => $request->only([
                'date_from', 'date_to', 'product_id', 'action_type',
                'user_id', 'quantity_direction', 'category',
            ]),
            'products' => Product::query()->orderBy('name')->get(['id', 'name']),
            'users' => User::query()
                ->whereIn('role', UserRole::adminRoles())
                ->orderBy('name')
                ->get(['id', 'name', 'email']),
            'actionTypes' => StockMovement::ACTION_TYPES,
            'categories' => ProductCategory::cases(),
        ]);
    }

    public function show(Request $request, StockMovement $stockMovement): View
    {
        abort_unless(
            in_array($request->user()?->role, [UserRole::PurchasingAdmin, UserRole::SuperAdmin], true),
            403,
        );

        $stockMovement->load(['product', 'user']);

        return view('admin.purchasing.stock-movements.show', [
            'movement' => $stockMovement,
        ]);
    }
}
