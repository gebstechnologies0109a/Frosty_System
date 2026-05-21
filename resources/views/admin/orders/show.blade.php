@extends('layouts.app')
@section('title', 'Order #'.$order->id)
@section('content')
@include('admin.partials.page-header', ['title' => 'Order #'.$order->id, 'subtitle' => $order->user->name.' → '.$order->distributor->name])
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body row g-3">
        <div class="col-md-3"><div class="small text-muted">Status</div><div class="fw-bold">{{ $order->status->value }}</div></div>
        <div class="col-md-3"><div class="small text-muted">Total points</div><div class="fw-bold">{{ $order->total_points }}</div></div>
        <div class="col-md-3"><div class="small text-muted">Amount</div><div class="fw-bold">₱{{ number_format($order->total_amount, 2) }}</div></div>
        <div class="col-md-3"><div class="small text-muted">Source</div><div class="fw-bold">{{ $order->source->value }}</div></div>
    </div>
</div>
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header fw-semibold">Order items</div>
    <table class="table table-sm mb-0">
        <thead class="table-light"><tr><th>Product</th><th class="text-end">Qty</th><th class="text-end">Price</th><th class="text-end">Points</th></tr></thead>
        <tbody>
        @foreach ($order->items as $item)
            <tr>
                <td>{{ $item->product?->name ?? '—' }}</td>
                <td class="text-end">{{ $item->qty }}</td>
                <td class="text-end">₱{{ number_format($item->price, 2) }}</td>
                <td class="text-end">{{ $item->points }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
<div class="d-flex flex-wrap gap-2 mb-3">
    <form method="post" action="{{ route('admin.orders.pending.approve', $order) }}">@csrf<button class="btn btn-success">Approve</button></form>
    <form method="post" action="{{ route('admin.orders.pending.reject', $order) }}">@csrf<button class="btn btn-outline-danger">Reject</button></form>
</div>
<form method="post" action="{{ route('admin.orders.pending.update-status', $order) }}" class="card border-0 shadow-sm p-3">@csrf @method('PATCH')
    <label class="form-label fw-semibold">Update status</label>
    <div class="d-flex gap-2">
        <select name="status" class="form-select form-select-sm" style="max-width:12rem">
            @foreach (\App\Enums\OrderStatus::cases() as $s)<option value="{{ $s->value }}" @selected($order->status === $s)>{{ $s->value }}</option>@endforeach
        </select>
        <button class="btn btn-sm btn-primary">Apply</button>
    </div>
</form>
<a href="{{ route('admin.orders.pending') }}" class="btn btn-link mt-2">Back to pending</a>
@endsection
