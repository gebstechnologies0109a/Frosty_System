@extends('layouts.app')
@section('title', 'Distributor Dashboard')
@section('content')
<h1 class="h3 mb-4">Distributor Dashboard</h1>
<div class="row g-4">
    <div class="col-lg-6">
        <div class="card p-3">
            <h2 class="h6">My operators ({{ $operators->count() }})</h2>
            <ul class="list-group list-group-flush">
                @forelse ($operators as $op)
                    <li class="list-group-item">{{ $op->name }} — {{ $op->email }}</li>
                @empty
                    <li class="list-group-item text-muted">No operators assigned.</li>
                @endforelse
            </ul>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card p-3">
            <h2 class="h6">Pending operator orders</h2>
            @forelse ($pendingOperatorOrders as $order)
                <div class="d-flex justify-content-between border-bottom py-2">
                    <span>#{{ $order->id }} {{ $order->user->name }} — {{ $order->total_points }} pts</span>
                    <form method="post" action="{{ route('distributor.orders.approve', $order) }}">@csrf<button class="btn btn-sm btn-success">Approve</button></form>
                </div>
            @empty
                <p class="text-muted mb-0">None pending.</p>
            @endforelse
            <a href="{{ route('distributor.orders.create') }}" class="btn btn-primary btn-sm mt-3">Order from Main</a>
        </div>
    </div>
</div>
@endsection
