<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderEngine;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

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

    public function show(Order $order): View
    {
        try {
            $order->load([
                'user:id,name,email,role',
                'operator:id,name,email',
                'distributor:id,name,is_main',
                'items.product:id,name,category',
                'approver:id,name,email',
            ]);

            return view('admin.orders.show', [
                'order' => $order,
                'backUrl' => $order->status === OrderStatus::Pending
                    ? route('admin.orders.pending')
                    : route('admin.orders.index'),
            ]);
        } catch (Throwable $e) {
            Log::error('Failed to load order details', [
                'order_id' => $order->id,
                'message' => $e->getMessage(),
            ]);

            abort(500, 'Unable to load order details. Please try again or contact support.');
        }
    }

    public function updateStatus(Request $request, Order $order, OrderEngine $engine): RedirectResponse
    {
        $request->validate([
            'status' => ['required', Rule::in([
                OrderStatus::Pending->value,
                OrderStatus::Approved->value,
                OrderStatus::Rejected->value,
                OrderStatus::Completed->value,
            ])],
        ]);

        $status = OrderStatus::from($request->input('status'));
        $user = $request->user();

        match ($status) {
            OrderStatus::Approved => $engine->approve($order, $user),
            OrderStatus::Rejected => $engine->reject($order, $user),
            OrderStatus::Completed => $engine->complete($order, $user),
            default => null,
        };

        return redirect()
            ->route('admin.orders.show', $order)
            ->with('success', 'Order status updated.');
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
