<?php

namespace App\Http\Controllers\Distributor;

use App\Http\Controllers\Controller;
use App\Models\Distributor;
use App\Models\Order;
use App\Models\Product;
use App\Services\OrderEngine;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(): View
    {
        $profile = Auth::user()->distributorProfile;

        if (! $profile) {
            abort(403);
        }

        return view('distributor.orders.index', [
            'distributor' => $profile,
            'operatorOrders' => Order::query()
                ->where('distributor_id', $profile->id)
                ->with(['user', 'items.product'])
                ->latest()
                ->paginate(15, ['*'], 'operator'),
            'myOrders' => Order::query()
                ->where('user_id', Auth::id())
                ->with('items.product', 'distributor')
                ->latest()
                ->paginate(15, ['*'], 'mine'),
        ]);
    }

    public function createFromMain(): View
    {
        return view('distributor.orders.create', [
            'products' => Product::query()->active()->with('prices')->orderBy('name')->get(),
        ]);
    }

    public function storeFromMain(Request $request, OrderEngine $engine): RedirectResponse
    {
        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.qty' => ['required', 'integer', 'min:1'],
        ]);

        $engine->create(Auth::user(), $validated['items'], Distributor::mainId());

        return redirect()->route('distributor.orders.index')->with('success', 'Order submitted to Main.');
    }

    public function approve(Request $request, Order $order, OrderEngine $engine): RedirectResponse
    {
        $engine->approve($order, $request->user());

        return back()->with('success', 'Order approved.');
    }
}
