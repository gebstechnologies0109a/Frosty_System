<?php

namespace App\Services;

use App\Enums\OrderType;
use App\Enums\UserRole;
use App\Models\Order;
use App\Models\User;
use App\Support\ListPage;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class AdminPosSalesLogService
{
    /** @return array<string, mixed> */
    public function indexData(Request $request): array
    {
        $query = Order::query()
            ->pos()
            ->with(['operator:id,name,email', 'items.operatorProduct'])
            ->latest();

        if ($request->filled('operator_id')) {
            $query->where('operator_id', $request->input('operator_id'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }

        if ($request->filled('amount_min')) {
            $query->where('total_amount', '>=', $request->input('amount_min'));
        }

        if ($request->filled('product_name')) {
            $query->whereHas('items.operatorProduct', fn ($q) => $q->where('product_name', 'like', '%'.$request->input('product_name').'%'));
        }

        $orders = $query->paginate(ListPage::perPage($request, 20))->withQueryString();
        $orders->getCollection()->transform(fn (Order $order) => [
            'id' => $order->id,
            'operator' => $order->operator?->name ?? '—',
            'operator_id' => $order->operator_id,
            'total' => (float) $order->total_amount,
            'cogs' => (float) ($order->cogs_total ?? 0),
            'profit' => (float) ($order->gross_profit ?? 0),
            'margin' => $order->total_amount > 0
                ? round(((float) $order->gross_profit / (float) $order->total_amount) * 100, 1)
                : 0,
            'at' => $order->created_at,
            'items' => $order->items->map(fn ($item) => [
                'product' => $item->operatorProduct?->product_name ?? $item->product?->name ?? '—',
                'qty' => $item->qty,
                'unit_price' => (float) $item->price,
                'line_total' => (float) ($item->line_total ?? $item->price * $item->qty),
                'cost' => (float) ($item->cost_price ?? 0),
            ]),
        ]);

        return [
            'orders' => $orders,
            'operators' => User::query()->where('role', UserRole::Operator)->orderBy('name')->get(['id', 'name']),
            'filters' => $request->only(['operator_id', 'date_from', 'date_to', 'product_name', 'amount_min']),
        ];
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $query = Order::query()->pos()->with(['operator', 'items.operatorProduct'])->latest();

        if ($request->filled('operator_id')) {
            $query->where('operator_id', $request->input('operator_id'));
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }

        $filename = 'pos-sales-'.Carbon::now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Order ID', 'Operator', 'Operator ID', 'Date', 'Total', 'COGS', 'Profit', 'Product', 'Qty', 'Unit Price', 'Line Total', 'Unit Cost']);

            $query->chunk(100, function ($orders) use ($out) {
                foreach ($orders as $order) {
                    foreach ($order->items as $item) {
                        fputcsv($out, [
                            $order->id,
                            $order->operator?->name,
                            $order->operator_id,
                            $order->created_at?->toDateTimeString(),
                            $order->total_amount,
                            $order->cogs_total,
                            $order->gross_profit,
                            $item->operatorProduct?->product_name ?? $item->product?->name,
                            $item->qty,
                            $item->price,
                            $item->line_total ?? $item->price * $item->qty,
                            $item->cost_price,
                        ]);
                    }
                }
            });

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
