@extends('layouts.app')
@section('title', 'Orders')
@section('content')
@include('admin.partials.page-header', [
    'title' => 'Orders',
    'subtitle' => 'All orders and Main purchasing queue',
    'actions' => '<a href="'.route('admin.orders.pending').'" class="btn btn-outline-primary btn-sm">Pending orders</a>
        <a href="'.route('admin.orders.analytics').'" class="btn btn-outline-secondary btn-sm">Analytics</a>',
])

<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white fw-semibold">All orders</div>
    <p class="small text-muted px-3 pt-2 mb-0">Every order in the system, newest first.</p>
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Placed by</th>
                    <th>Distributor</th>
                    <th class="text-end">Total</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
            @forelse ($allOrders as $order)
                <tr>
                    <td>
                        <a href="{{ route('admin.orders.show', $order) }}" class="fw-semibold text-decoration-none">#{{ $order->id }}</a>
                    </td>
                    <td>{{ $order->user?->name ?? '—' }}</td>
                    <td>
                        {{ $order->distributor?->name ?? '—' }}
                        @if ($order->distributor?->is_main)
                            <span class="badge text-bg-info ms-1">Main</span>
                        @endif
                    </td>
                    <td class="text-end">₱{{ number_format($order->total_amount, 2) }}</td>
                    <td><span class="badge text-bg-light text-dark border">{{ $order->status->value }}</span></td>
                    <td class="text-end">
                        <a href="{{ route('admin.orders.show', $order) }}" class="btn btn-sm btn-outline-primary">View</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center text-muted py-4">No orders yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white fw-semibold">Purchasing queue (Main distributor only)</div>
    <p class="small text-muted px-3 pt-2 mb-0">Pending orders routed to Main for purchasing admin approval.</p>
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Placed by</th>
                    <th class="text-end">Pts</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
            @forelse ($mainQueueOrders as $order)
                <tr>
                    <td>
                        <a href="{{ route('admin.orders.show', $order) }}" class="fw-semibold text-decoration-none">#{{ $order->id }}</a>
                    </td>
                    <td>{{ $order->user?->name ?? '—' }} <span class="text-muted small">({{ $order->source?->value }})</span></td>
                    <td class="text-end">{{ $order->total_points }}</td>
                    <td><span class="badge text-bg-secondary">{{ $order->status->value }}</span></td>
                    <td class="text-end text-nowrap">
                        <a href="{{ route('admin.orders.show', $order) }}" class="btn btn-sm btn-outline-primary">View</a>
                        @if ($order->status === \App\Enums\OrderStatus::Pending)
                            <form class="d-inline" method="post" action="{{ route('admin.orders.approve', $order) }}">@csrf<button type="submit" class="btn btn-sm btn-success">Approve</button></form>
                            <form class="d-inline" method="post" action="{{ route('admin.orders.reject', $order) }}">@csrf<button type="submit" class="btn btn-sm btn-outline-danger">Reject</button></form>
                        @elseif ($order->status === \App\Enums\OrderStatus::Approved)
                            <form class="d-inline" method="post" action="{{ route('admin.orders.complete', $order) }}">@csrf<button type="submit" class="btn btn-sm btn-secondary">Complete</button></form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-center text-muted py-4">No pending Main orders.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
