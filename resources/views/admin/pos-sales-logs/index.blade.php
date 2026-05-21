@extends('layouts.app')
@section('title', 'POS Sales Logs')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h1 class="h3 mb-1">POS Sales Logs</h1>
        <p class="text-muted small mb-0">Full transaction history (Super Admin only)</p>
    </div>
    <a href="{{ route('admin.pos-sales-logs.export', request()->query()) }}" class="btn btn-outline-success btn-sm">Export CSV</a>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <form method="get" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small">Operator</label>
                <select name="operator_id" class="form-select form-select-sm">
                    <option value="">All</option>
                    @foreach ($operators as $op)
                        <option value="{{ $op->id }}" @selected(($filters['operator_id'] ?? '') == $op->id)>{{ $op->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small">From</label>
                <input type="date" name="date_from" class="form-control form-control-sm" value="{{ $filters['date_from'] ?? '' }}">
            </div>
            <div class="col-md-2">
                <label class="form-label small">To</label>
                <input type="date" name="date_to" class="form-control form-control-sm" value="{{ $filters['date_to'] ?? '' }}">
            </div>
            <div class="col-md-2">
                <label class="form-label small">Product name</label>
                <input type="text" name="product_name" class="form-control form-control-sm" value="{{ $filters['product_name'] ?? '' }}">
            </div>
            <div class="col-md-2">
                <label class="form-label small">Min amount</label>
                <input type="number" name="amount_min" step="0.01" class="form-control form-control-sm" value="{{ $filters['amount_min'] ?? '' }}">
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary btn-sm">Filter</button>
            </div>
        </form>
    </div>
</div>

@foreach ($orders as $order)
    <div class="card shadow-sm mb-3">
        <div class="card-header bg-white d-flex justify-content-between flex-wrap gap-2">
            <span><strong>Order #{{ $order['id'] }}</strong> — {{ $order['operator'] }} (ID {{ $order['operator_id'] }})</span>
            <span class="text-muted small">{{ $order['at']->format('M j, Y g:i A') }}</span>
        </div>
        <div class="card-body py-2">
            <div class="row mb-2 small">
                <div class="col-md-3">Total: <strong>₱{{ number_format($order['total'], 2) }}</strong></div>
                <div class="col-md-3">COGS: ₱{{ number_format($order['cogs'], 2) }}</div>
                <div class="col-md-3">Profit: <span class="text-success">₱{{ number_format($order['profit'], 2) }}</span></div>
                <div class="col-md-3">Margin: {{ $order['margin'] }}%</div>
            </div>
            <table class="table table-sm mb-0">
                <thead class="table-light"><tr><th>Product</th><th class="text-end">Qty</th><th class="text-end">Unit</th><th class="text-end">Line</th><th class="text-end">Cost</th></tr></thead>
                <tbody>
                @foreach ($order['items'] as $item)
                    <tr>
                        <td>{{ $item['product'] }}</td>
                        <td class="text-end">{{ $item['qty'] }}</td>
                        <td class="text-end">₱{{ number_format($item['unit_price'], 2) }}</td>
                        <td class="text-end">₱{{ number_format($item['line_total'], 2) }}</td>
                        <td class="text-end">₱{{ number_format($item['cost'], 2) }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endforeach

@include('partials.list-pagination', ['paginator' => $orders])
@endsection
