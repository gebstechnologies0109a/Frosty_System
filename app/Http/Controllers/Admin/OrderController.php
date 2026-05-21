<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderEngine;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(): View
    {
        return view('admin.orders.index', [
            'orders' => Order::query()
                ->forPurchasingQueue()
                ->with(['user', 'distributor', 'items.product'])
                ->latest()
                ->paginate(20),
            'allOrders' => Order::query()
                ->with(['user', 'distributor'])
                ->latest()
                ->paginate(20, ['*'], 'all'),
        ]);
    }

    public function approve(Request $request, Order $order, OrderEngine $engine): RedirectResponse
    {
        $engine->approve($order, $request->user());

        return back()->with('success', 'Order approved; rebates processed.');
    }

    public function reject(Request $request, Order $order, OrderEngine $engine): RedirectResponse
    {
        $engine->reject($order, $request->user());

        return back()->with('success', 'Order rejected.');
    }

    public function complete(Request $request, Order $order, OrderEngine $engine): RedirectResponse
    {
        $engine->complete($order, $request->user());

        return back()->with('success', 'Order marked completed.');
    }
}
