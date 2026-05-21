<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderEngine;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminPendingOrderController extends Controller
{
    public function index(Request $request): View
    {
        $query = Order::query()
            ->where('status', OrderStatus::Pending)
            ->with(['user:id,name,email', 'distributor:id,name', 'items.product:id,name']);

        if ($request->filled('q')) {
            $q = '%'.$request->string('q').'%';
            $query->where(function ($b) use ($q) {
                $b->where('id', 'like', $q)
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', $q)->orWhere('email', 'like', $q));
            });
        }

        if ($request->filled('distributor_id')) {
            $query->where('distributor_id', $request->integer('distributor_id'));
        }

        return view('admin.orders.pending', [
            'orders' => $query->latest()->paginate(20)->withQueryString(),
            'distributors' => \App\Models\Distributor::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function show(Order $order): RedirectResponse
    {
        return redirect()->route('admin.orders.show', $order);
    }

    public function updateStatus(Request $request, Order $order): RedirectResponse
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
        $engine = app(OrderEngine::class);
        $user = $request->user();

        match ($status) {
            OrderStatus::Approved => $engine->approve($order, $user),
            OrderStatus::Rejected => $engine->reject($order, $user),
            OrderStatus::Completed => $engine->complete($order, $user),
            default => null,
        };

        return redirect()
            ->route('admin.orders.pending')
            ->with('success', 'Order status updated.');
    }

    public function approve(Request $request, Order $order, OrderEngine $engine): RedirectResponse
    {
        $engine->approve($order, $request->user());

        return back()->with('success', 'Order approved.');
    }

    public function reject(Request $request, Order $order, OrderEngine $engine): RedirectResponse
    {
        $engine->reject($order, $request->user());

        return back()->with('success', 'Order rejected.');
    }
}
