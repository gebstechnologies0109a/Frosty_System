@extends('layouts.app')
@section('title', 'Admin Dashboard')
@section('content')
@include('admin.partials.page-header', [
    'title' => 'Admin Dashboard',
    'subtitle' => 'Super Admin overview',
])
@php
    $statLinks = [
        'operators' => route('admin.operators.index'),
        'distributors' => route('admin.distributors.index'),
        'products' => route('admin.products.index'),
        'pending_orders' => route('admin.orders.pending'),
        'pending_withdrawals' => route('admin.withdrawals.pending'),
    ];
@endphp
<div class="d-flex justify-content-end mb-3">
    @if (auth()->user()->role === \App\Enums\UserRole::SuperAdmin)
        <a href="{{ route('admin.users.create') }}" class="btn btn-primary">Add User</a>
    @endif
</div>
<div class="row g-3">
    @foreach ($stats as $label => $value)
        @php $href = $statLinks[$label] ?? null; @endphp
        <div class="col-md-4">
            @if ($href)
                <a href="{{ $href }}" class="card p-3 text-decoration-none text-reset h-100 admin-stat-card">
                    <div class="text-muted small text-capitalize">{{ str_replace('_', ' ', $label) }}</div>
                    <div class="fs-3 fw-bold">{{ $value }}</div>
                    <div class="small text-primary mt-2">Manage &rarr;</div>
                </a>
            @else
                <div class="card p-3 h-100">
                    <div class="text-muted small text-capitalize">{{ str_replace('_', ' ', $label) }}</div>
                    <div class="fs-3 fw-bold">{{ $value }}</div>
                </div>
            @endif
        </div>
    @endforeach
</div>
<style>
.admin-stat-card { transition: transform .15s, box-shadow .15s; }
.admin-stat-card:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(0,0,0,.08); color: inherit; }
</style>
@endsection
