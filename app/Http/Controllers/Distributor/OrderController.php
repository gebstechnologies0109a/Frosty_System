<?php

namespace App\Http\Controllers\Distributor;

use App\Exceptions\PaymentProofRequiredException;
use App\Http\Controllers\Controller;
use App\Models\Distributor;
use App\Models\Order;
use App\Models\Product;
use App\Services\OrderEngine;
use App\Support\ListPage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $profile = Auth::user()->distributorProfile;

        if (! $profile) {
            abort(403);
        }

        $perPage = ListPage::perPage($request, 20);

        return view('distributor.orders.index', [
            'distributor' => $profile,
            'operatorOrders' => Order::query()
                ->where('distributor_id', $profile->id)
                ->with(['user', 'items.product'])
                ->latest()
                ->paginate($perPage, ['*'], 'operator')
                ->withQueryString(),
            'myOrders' => Order::query()
                ->where('user_id', Auth::id())
                ->with('items.product', 'distributor')
                ->latest()
                ->paginate($perPage, ['*'], 'mine')
                ->withQueryString(),
        ]);
    }

    public function createFromMain(): View
    {
        $region = auth()->user()->priceRegion();

        return view('distributor.orders.create', [
            'products' => Product::query()
                ->active()
                ->forPricingRegion($region)
                ->with(['prices' => fn ($q) => $q->where('region', $region)])
                ->orderBy('name')
                ->get(),
            'priceRegion' => $region,
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
        try {
            $engine->approve($order, $request->user());
        } catch (PaymentProofRequiredException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Order approved.');
    }
}
