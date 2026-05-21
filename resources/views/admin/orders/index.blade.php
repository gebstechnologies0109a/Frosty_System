@extends('layouts.app')
@section('title', 'Orders')
@section('content')
@include('admin.partials.page-header', [
    'title' => 'Orders',
    'subtitle' => 'Purchasing queue (Main) and all orders',
    'actions' => '<a href="'.route('admin.orders.pending').'" class="btn btn-outline-primary btn-sm">Pending orders</a>
        <a href="'.route('admin.orders.analytics').'" class="btn btn-outline-secondary btn-sm">Analytics</a>',
])
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white fw-semibold">Purchasing queue (Main distributor)</div>
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Placed by</th>
                    <th>Route</th>
                    <th class="text-end">Pts</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
            @forelse ($orders as $order)
                <tr>
                    <td>
                        <a href="{{ route('admin.orders.show', $order) }}" class="fw-semibold text-decoration-none">#{{ $order->id }}</a>
                    </td>
                    <td>{{ $order->user?->name ?? '—' }} <span class="text-muted small">({{ $order->source?->value }})</span></td>
                    <td>{{ $order->distributor?->name ?? '—' }}</td>
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
                <tr><td colspan="6" class="text-center text-muted py-4">No pending Main orders.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@include('partials.list-pagination', ['paginator' => $orders])

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white fw-semibold">All orders</div>
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0 align-middle">
            <thead class="table-light"><tr><th>#</th><th>User</th><th>Distributor</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
            <tbody>
            @foreach ($allOrders as $order)
                <tr>
                    <td><a href="{{ route('admin.orders.show', $order) }}" class="fw-semibold text-decoration-none">#{{ $order->id }}</a></td>
                    <td>{{ $order->user?->name ?? '—' }}</td>
                    <td>{{ $order->distributor?->name ?? '—' }}</td>
                    <td><span class="badge text-bg-light text-dark border">{{ $order->status->value }}</span></td>
                    <td class="text-end"><a href="{{ route('admin.orders.show', $order) }}" class="btn btn-sm btn-outline-primary">View</a></td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@include('partials.list-pagination', ['paginator' => $allOrders])
@endsection
