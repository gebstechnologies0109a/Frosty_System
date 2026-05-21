@extends('layouts.app')
@section('title', 'Admin Dashboard')
@section('content')
@include('admin.partials.page-header', [
    'title' => 'Admin Dashboard',
    'subtitle' => 'Super Admin overview',
    'actions' => '<a href="'.route('admin.users.create').'" class="btn btn-primary">Add User</a>',
])
@php
    $statLinks = [
        'operators' => route('admin.operators.index'),
        'distributors' => route('admin.distributors.index'),
        'users' => route('admin.users.index'),
        'products' => route('admin.products.index'),
        'pos_logs' => route('admin.pos.logs'),
        'pos_closings' => route('admin.pos.closings'),
    ];
    $statLabels = [
        'operators' => 'Operators',
        'distributors' => 'Distributors',
        'users' => 'Users',
        'products' => 'Products',
        'pos_logs' => 'POS Logs',
        'pos_closings' => 'POS Closings',
    ];
@endphp
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <span class="fw-semibold">Recent supply orders</span>
        <a href="{{ route('admin.orders.index') }}" class="btn btn-sm btn-outline-primary">All orders</a>
    </div>
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Operator</th>
                    <th>Distributor</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            @forelse ($recentOrders as $order)
                <tr>
                    <td><a href="{{ route('admin.orders.show', $order) }}" class="fw-semibold text-decoration-none">#{{ $order->id }}</a></td>
                    <td>{{ $order->user?->name ?? '—' }}</td>
                    <td>{{ $order->distributor?->name ?? '—' }}</td>
                    <td><span class="badge text-bg-light text-dark border">{{ $order->status->value }}</span></td>
                    <td class="text-end"><a href="{{ route('admin.orders.show', $order) }}" class="btn btn-sm btn-outline-secondary">View</a></td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-muted text-center py-3">No orders yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="row g-3">
    @foreach ($stats as $label => $value)
        @php $href = $statLinks[$label] ?? null; @endphp
        <div class="col-md-4 col-lg-4">
            <a href="{{ $href }}" class="card p-3 text-decoration-none text-reset h-100 admin-stat-card">
                <div class="text-muted small">{{ $statLabels[$label] ?? str_replace('_', ' ', $label) }}</div>
                <div class="fs-3 fw-bold">{{ $value }}</div>
                <div class="small text-primary mt-2">Manage &rarr;</div>
            </a>
        </div>
    @endforeach
</div>
<style>
.admin-stat-card { transition: transform .15s, box-shadow .15s; }
.admin-stat-card:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(0,0,0,.08); color: inherit; }
</style>
@endsection
