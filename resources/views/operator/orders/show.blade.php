@extends('layouts.operator')
@section('header_title', 'Order #'.$order->id)
@section('title', 'Order #'.$order->id)
@section('content')
@php
    $statusClass = match ($order->status) {
        \App\Enums\OrderStatus::Pending => 'warning',
        \App\Enums\OrderStatus::Approved => 'success',
        \App\Enums\OrderStatus::Rejected => 'danger',
        \App\Enums\OrderStatus::Completed => 'secondary',
    };
@endphp

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <a href="{{ route('operator.orders.index') }}" class="btn btn-outline-secondary btn-sm">← Order history</a>
    <span class="badge text-bg-{{ $statusClass }} fs-6">{{ ucfirst($order->status->value) }}</span>
</div>

@if (session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if (session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-6 col-md-3">
                <div class="small text-muted">Order #</div>
                <div class="fw-semibold">{{ $order->id }}</div>
            </div>
            <div class="col-6 col-md-3">
                <div class="small text-muted">Total amount</div>
                <div class="fw-semibold">₱{{ number_format($order->total_amount, 2) }}</div>
            </div>
            <div class="col-6 col-md-3">
                <div class="small text-muted">Total points</div>
                <div class="fw-semibold">{{ $order->total_points }}</div>
            </div>
            <div class="col-6 col-md-3">
                <div class="small text-muted">Pricing region</div>
                <div class="fw-semibold">{{ $pricingRegionLabel ?? '—' }}</div>
            </div>
            <div class="col-12 col-md-6">
                <div class="small text-muted">Distributor</div>
                <div class="fw-semibold">
                    {{ $order->distributor?->name ?? '—' }}
                    @if ($order->distributor?->is_main)
                        <span class="badge text-bg-info">Main</span>
                    @endif
                </div>
            </div>
            <div class="col-12 col-md-6">
                <div class="small text-muted">Submitted</div>
                <div>{{ $order->created_at?->format('M j, Y g:i A') ?? '—' }}</div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white fw-semibold">Products</div>
    <div class="table-responsive">
        <table class="table table-sm mb-0">
            <thead class="table-light">
                <tr>
                    <th>Product</th>
                    <th class="text-end">Qty</th>
                    <th class="text-end">Price</th>
                    <th class="text-end">Subtotal</th>
                </tr>
            </thead>
            <tbody>
            @foreach ($order->items as $item)
                <tr>
                    <td>{{ $item->product?->name ?? 'Product #'.$item->product_id }}</td>
                    <td class="text-end">{{ $item->qty }}</td>
                    <td class="text-end">₱{{ number_format($item->price, 2) }}</td>
                    <td class="text-end">₱{{ number_format($item->line_total ?? ($item->price * $item->qty), 2) }}</td>
                </tr>
            @endforeach
            </tbody>
            <tfoot class="table-light">
                <tr>
                    <th colspan="3" class="text-end">Total</th>
                    <th class="text-end">₱{{ number_format($order->total_amount, 2) }}</th>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white fw-semibold">Order notes</div>
    <div class="card-body">
        @if (filled($order->notes))
            <p class="mb-0">{{ $order->notes }}</p>
        @else
            <p class="text-muted mb-0">No notes on this order.</p>
        @endif
    </div>
</div>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white fw-semibold">Proof of payment</div>
    <div class="card-body">
        @if ($paymentProofUrl)
            <div class="d-flex flex-wrap align-items-start gap-3 mb-3">
                @if ($order->paymentProofIsImage())
                    <a href="{{ $paymentProofUrl }}" target="_blank" rel="noopener">
                        <img src="{{ $paymentProofUrl }}" alt="Payment proof" class="rounded border"
                             style="max-width:140px;max-height:140px;object-fit:cover">
                    </a>
                @else
                    <div class="rounded border bg-light px-4 py-3 text-center">
                        <div class="fw-semibold text-danger">PDF</div>
                        <a href="{{ $paymentProofUrl }}" target="_blank" rel="noopener" class="small">Open file</a>
                    </div>
                @endif
            </div>
        @else
            <p class="text-muted mb-3">No proof uploaded yet.</p>
        @endif

        @if (in_array($order->status, [\App\Enums\OrderStatus::Pending, \App\Enums\OrderStatus::Rejected], true))
            <form method="post" action="{{ route('operator.orders.payment-proof', $order) }}" enctype="multipart/form-data">
                @csrf
                <label class="form-label small" for="payment_proof">{{ $paymentProofUrl ? 'Replace proof' : 'Upload proof' }}</label>
                <input type="file" name="payment_proof" id="payment_proof" class="form-control form-control-sm mb-2"
                       accept="image/*,application/pdf" required>
                <div class="form-text mb-2">JPG, PNG, HEIC, or PDF. Max 5 MB.</div>
                @error('payment_proof')<div class="text-danger small mb-2">{{ $message }}</div>@enderror
                <button type="submit" class="btn btn-outline-primary btn-sm">{{ $paymentProofUrl ? 'Replace proof' : 'Upload proof' }}</button>
            </form>
        @endif
    </div>
</div>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white fw-semibold">Status timeline</div>
    <div class="card-body">
        <ul class="list-unstyled mb-0">
            @foreach ($timeline as $event)
                <li class="d-flex gap-3 mb-3">
                    <div class="flex-shrink-0 rounded-circle bg-primary" style="width:10px;height:10px;margin-top:6px"></div>
                    <div>
                        <div class="fw-semibold">{{ $event['label'] }}</div>
                        <div class="small text-muted">
                            {{ $event['at']?->format('M j, Y g:i A') ?? '—' }}
                            @if ($event['detail'])
                                · {{ $event['detail'] }}
                            @endif
                        </div>
                    </div>
                </li>
            @endforeach
        </ul>
        @if ($activity->isNotEmpty())
            <hr>
            <div class="small text-muted mb-2">Activity</div>
            <ul class="list-unstyled small mb-0">
                @foreach ($activity as $log)
                    <li class="mb-2">
                        <span class="text-muted">{{ $log->created_at?->format('M j, g:i A') }}</span>
                        — {{ str_replace('.', ' ', $log->action) }}
                        @if ($log->user && $log->user_id !== $order->user_id)
                            <span class="text-muted">({{ $log->user->name }})</span>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</div>

<div class="d-flex flex-wrap gap-2">
    @if ($order->status === \App\Enums\OrderStatus::Pending)
        <a href="{{ route('operator.orders.edit', $order) }}" class="btn btn-primary">Edit order</a>
    @endif
    @if ($order->status === \App\Enums\OrderStatus::Rejected)
        <form method="post" action="{{ route('operator.orders.resubmit', $order) }}" class="d-inline"
              onsubmit="return confirm('Re-submit this order to your distributor?');">
            @csrf
            <button type="submit" class="btn btn-warning">Re-submit order</button>
        </form>
    @endif
</div>
@endsection
