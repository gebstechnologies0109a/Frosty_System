@extends('layouts.operator')
@section('header_title', 'Orders')
@section('title', 'Order History')
@section('content')
<h1 class="h4 mb-3">Order history</h1>
<a href="{{ route('operator.orders.create') }}" class="btn btn-primary btn-sm mb-3">New order</a>
<div class="table-responsive">
    <table class="table table-sm bg-white shadow-sm align-middle">
        <thead>
            <tr>
                <th>#</th>
                <th>Distributor</th>
                <th class="text-end">Total</th>
                <th class="text-end">Pts</th>
                <th>Status</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        @forelse ($orders as $order)
            @php
                $statusClass = match ($order->status) {
                    \App\Enums\OrderStatus::Pending => 'warning',
                    \App\Enums\OrderStatus::Approved => 'success',
                    \App\Enums\OrderStatus::Rejected => 'danger',
                    \App\Enums\OrderStatus::Completed => 'secondary',
                };
            @endphp
            <tr>
                <td>{{ $order->id }}</td>
                <td>{{ $order->distributor?->name ?? 'Main' }}</td>
                <td class="text-end">₱{{ number_format($order->total_amount, 2) }}</td>
                <td class="text-end">{{ $order->total_points }}</td>
                <td><span class="badge text-bg-{{ $statusClass }}">{{ ucfirst($order->status->value) }}</span></td>
                <td class="text-end text-nowrap">
                    <a href="{{ route('operator.orders.show', $order->id) }}" class="btn btn-outline-primary btn-sm">View</a>
                    @if (in_array($order->status, [\App\Enums\OrderStatus::Pending, \App\Enums\OrderStatus::Rejected], true))
                        <a href="{{ route('operator.orders.edit', $order->id) }}" class="btn btn-primary btn-sm">
                            {{ $order->status === \App\Enums\OrderStatus::Rejected ? 'Re-submit' : 'Edit' }}
                        </a>
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="6" class="text-muted text-center py-4">No orders yet.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@include('partials.list-pagination', ['paginator' => $orders])
@endsection
