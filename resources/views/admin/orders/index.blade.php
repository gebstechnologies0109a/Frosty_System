@extends('layouts.app')
@section('title', 'Orders')
@section('content')
<h1 class="h4 mb-3">Purchasing queue (Main)</h1>
<table class="table table-sm bg-white shadow-sm mb-4">
    <thead><tr><th>#</th><th>Placed by</th><th>Route</th><th class="text-end">Pts</th><th>Status</th><th></th></tr></thead>
    <tbody>
    @forelse ($orders as $order)
        <tr>
            <td>{{ $order->id }}</td>
            <td>{{ $order->user->name }} <span class="text-muted small">({{ $order->source->value }})</span></td>
            <td>{{ $order->distributor->name }}</td>
            <td class="text-end">{{ $order->total_points }}</td>
            <td>{{ $order->status->value }}</td>
            <td>
                @if ($order->status === \App\Enums\OrderStatus::Pending)
                    <form class="d-inline" method="post" action="{{ route('admin.orders.approve', $order) }}">@csrf<button class="btn btn-sm btn-success">Approve</button></form>
                    <form class="d-inline" method="post" action="{{ route('admin.orders.reject', $order) }}">@csrf<button class="btn btn-sm btn-outline-danger">Reject</button></form>
                @elseif ($order->status === \App\Enums\OrderStatus::Approved)
                    <form class="d-inline" method="post" action="{{ route('admin.orders.complete', $order) }}">@csrf<button class="btn btn-sm btn-secondary">Complete</button></form>
                @endif
            </td>
        </tr>
    @empty
        <tr><td colspan="6" class="text-center text-muted">No pending Main orders.</td></tr>
    @endforelse
    </tbody>
</table>
{{ $orders->links() }}

<h2 class="h6 mt-4">All orders</h2>
<table class="table table-sm bg-white shadow-sm">
    <thead><tr><th>#</th><th>User</th><th>Distributor</th><th>Status</th></tr></thead>
    <tbody>
    @foreach ($allOrders as $order)
        <tr><td>{{ $order->id }}</td><td>{{ $order->user->name }}</td><td>{{ $order->distributor->name }}</td><td>{{ $order->status->value }}</td></tr>
    @endforeach
    </tbody>
</table>
{{ $allOrders->links() }}
@endsection
