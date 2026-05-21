@extends('layouts.app')
@section('title', 'Order #'.$order->id)
@section('content')
@php
    $statusClass = match ($order->status) {
        \App\Enums\OrderStatus::Pending => 'warning',
        \App\Enums\OrderStatus::Approved => 'success',
        \App\Enums\OrderStatus::Rejected => 'danger',
        \App\Enums\OrderStatus::Completed => 'secondary',
    };
    $placedBy = $order->user;
    $operator = $order->operator;
@endphp
@include('admin.partials.page-header', [
    'title' => 'Order #'.$order->id,
    'subtitle' => ($placedBy?->name ?? 'Unknown').' · '.($order->distributor?->name ?? 'No distributor'),
    'actions' => '<a href="'.$backUrl.'" class="btn btn-outline-secondary">← Back</a>',
])

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="small text-muted">Status</div>
                <span class="badge text-bg-{{ $statusClass }}">{{ ucfirst($order->status->value) }}</span>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="small text-muted">Total amount</div>
                <div class="fs-5 fw-bold">₱{{ number_format($order->total_amount, 2) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="small text-muted">Total points</div>
                <div class="fs-5 fw-bold">{{ number_format($order->total_points) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="small text-muted">Gross profit</div>
                <div class="fs-5 fw-bold">₱{{ number_format($order->gross_profit ?? 0, 2) }}</div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white fw-semibold">Order metadata</div>
            <div class="card-body">
                <dl class="row mb-0 small">
                    <dt class="col-sm-4">Placed by</dt>
                    <dd class="col-sm-8">{{ $placedBy?->name ?? '—' }} @if($placedBy?->email)<span class="text-muted">({{ $placedBy->email }})</span>@endif</dd>
                    <dt class="col-sm-4">Operator</dt>
                    <dd class="col-sm-8">{{ $operator?->name ?? '—' }}</dd>
                    <dt class="col-sm-4">Distributor</dt>
                    <dd class="col-sm-8">{{ $order->distributor?->name ?? '—' }} @if($order->distributor?->is_main)<span class="badge text-bg-info ms-1">Main</span>@endif</dd>
                    <dt class="col-sm-4">Source</dt>
                    <dd class="col-sm-8">{{ $order->source?->value ?? '—' }}</dd>
                    <dt class="col-sm-4">Type</dt>
                    <dd class="col-sm-8">{{ $order->order_type?->value ?? '—' }}</dd>
                    <dt class="col-sm-4">Payment</dt>
                    <dd class="col-sm-8">{{ $order->payment_method?->value ?? '—' }}</dd>
                    <dt class="col-sm-4">Price region</dt>
                    <dd class="col-sm-8">{{ $order->price_region?->label() ?? '—' }}</dd>
                    <dt class="col-sm-4">COGS</dt>
                    <dd class="col-sm-8">₱{{ number_format($order->cogs_total ?? 0, 2) }}</dd>
                </dl>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white fw-semibold">Timeline</div>
            <div class="card-body">
                <dl class="row mb-0 small">
                    <dt class="col-sm-4">Created</dt>
                    <dd class="col-sm-8">{{ $order->created_at?->format('M j, Y g:i A') ?? '—' }}</dd>
                    <dt class="col-sm-4">Updated</dt>
                    <dd class="col-sm-8">{{ $order->updated_at?->format('M j, Y g:i A') ?? '—' }}</dd>
                    <dt class="col-sm-4">Approved</dt>
                    <dd class="col-sm-8">
                        @if ($order->approved_at)
                            {{ $order->approved_at->format('M j, Y g:i A') }}
                            @if ($order->approver)<span class="text-muted"> by {{ $order->approver->name }}</span>@endif
                        @else
                            —
                        @endif
                    </dd>
                    <dt class="col-sm-4">Completed</dt>
                    <dd class="col-sm-8">{{ $order->completed_at?->format('M j, Y g:i A') ?? '—' }}</dd>
                </dl>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white fw-semibold">Order items ({{ $order->items->count() }})</div>
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>Product</th>
                    <th>Category</th>
                    <th class="text-end">Qty</th>
                    <th class="text-end">Unit price</th>
                    <th class="text-end">Line total</th>
                    <th class="text-end">Points</th>
                </tr>
            </thead>
            <tbody>
            @forelse ($order->items as $item)
                <tr>
                    <td class="fw-medium">{{ $item->product?->name ?? 'Product #'.$item->product_id }}</td>
                    <td class="text-muted small">{{ $item->product?->category?->label() ?? '—' }}</td>
                    <td class="text-end">{{ $item->qty }}</td>
                    <td class="text-end">₱{{ number_format($item->price, 2) }}</td>
                    <td class="text-end">₱{{ number_format($item->line_total ?? ($item->price * $item->qty), 2) }}</td>
                    <td class="text-end">{{ $item->points }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center text-muted py-4">No line items on this order.</td></tr>
            @endforelse
            </tbody>
            @if ($order->items->isNotEmpty())
            <tfoot class="table-light">
                <tr>
                    <th colspan="4" class="text-end">Order total</th>
                    <th class="text-end">₱{{ number_format($order->total_amount, 2) }}</th>
                    <th class="text-end">{{ number_format($order->total_points) }}</th>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>
</div>

@if ($order->status === \App\Enums\OrderStatus::Pending)
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-white fw-semibold">Actions</div>
        <div class="card-body d-flex flex-wrap gap-2">
            <form method="post" action="{{ route('admin.orders.approve', $order) }}">@csrf<button type="submit" class="btn btn-success">Approve</button></form>
            <form method="post" action="{{ route('admin.orders.reject', $order) }}">@csrf<button type="submit" class="btn btn-outline-danger">Reject</button></form>
        </div>
    </div>
@elseif ($order->status === \App\Enums\OrderStatus::Approved)
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <form method="post" action="{{ route('admin.orders.complete', $order) }}">@csrf<button type="submit" class="btn btn-secondary">Mark completed</button></form>
        </div>
    </div>
@endif

<form method="post" action="{{ route('admin.orders.update-status', $order) }}" class="card border-0 shadow-sm">
    @csrf @method('PATCH')
    <div class="card-body">
        <label class="form-label fw-semibold">Update status</label>
        <div class="d-flex flex-wrap gap-2 align-items-center">
            <select name="status" class="form-select form-select-sm" style="max-width:14rem">
                @foreach (\App\Enums\OrderStatus::cases() as $s)
                    <option value="{{ $s->value }}" @selected($order->status === $s)>{{ ucfirst($s->value) }}</option>
                @endforeach
            </select>
            <button type="submit" class="btn btn-sm btn-primary">Apply status</button>
        </div>
    </div>
</form>
@endsection
