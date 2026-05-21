<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Exceptions\PaymentProofRequiredException;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderEngine;
use App\Services\OrderPaymentProofService;
use App\Support\ListPage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $perPage = ListPage::perPage($request, 20);

        return view('admin.orders.index', [
            'orders' => Order::query()
                ->forPurchasingQueue()
                ->with(['user', 'distributor', 'items.product'])
                ->latest()
                ->paginate($perPage)
                ->withQueryString(),
            'allOrders' => Order::query()
                ->with(['user', 'distributor'])
                ->latest()
                ->paginate($perPage, ['*'], 'all')
                ->withQueryString(),
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
                'paymentProofUrl' => $order->paymentProofUrl(),
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

    public function downloadPaymentProof(Order $order, OrderPaymentProofService $proofs): StreamedResponse
    {
        abort_unless(
            $order->payment_proof_path && Storage::disk(OrderPaymentProofService::DISK)->exists($order->payment_proof_path),
            404,
        );

        return Storage::disk(OrderPaymentProofService::DISK)->download(
            $order->payment_proof_path,
            $proofs->downloadFilename($order->id, $order->payment_proof_path),
        );
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

        try {
            match ($status) {
                OrderStatus::Approved => $engine->approve($order, $user),
                OrderStatus::Rejected => $engine->reject($order, $user),
                OrderStatus::Completed => $engine->complete($order, $user),
                default => null,
            };
        } catch (PaymentProofRequiredException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('admin.orders.show', $order)
            ->with('success', 'Order status updated.');
    }

    public function approve(Request $request, Order $order, OrderEngine $engine): RedirectResponse
    {
        try {
            $engine->approve($order, $request->user());
        } catch (PaymentProofRequiredException $e) {
            return back()->with('error', $e->getMessage());
        }

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
