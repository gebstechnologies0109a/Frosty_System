<?php

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Models\Distributor;
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
        return view('operator.orders.index', [
            'orders' => Auth::user()->orders()->with(['items.product', 'distributor'])->latest()->paginate(15),
        ]);
    }

    public function create(): View
    {
        return view('operator.orders.create', [
            'products' => Product::query()->active()->with('prices')->orderBy('name')->get(),
            'distributors' => Distributor::query()->orderByDesc('is_main')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, OrderEngine $engine): RedirectResponse
    {
        $validated = $request->validate([
            'distributor_id' => ['required', 'exists:distributors,id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.qty' => ['required', 'integer', 'min:1'],
        ]);

        $engine->create(Auth::user(), $validated['items'], (int) $validated['distributor_id']);

        return redirect()->route('operator.orders.index')->with('success', 'Order submitted.');
    }
}
