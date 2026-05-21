@extends('layouts.operator')
@section('title', 'Dashboard')
@section('header_title', 'Dashboard')

@section('content')
<div class="frosty-grid-quick mb-3">
        <a href="{{ route('operator.pos.index') }}" class="operator-quick-tile text-center">
            <i class="fa-solid fa-cash-register"></i>
            <span class="small fw-semibold">POS</span>
        </a>
        <a href="{{ route('operator.supplies-inventory.index') }}" class="operator-quick-tile text-center">
            <i class="fa-solid fa-boxes-stacked"></i>
            <span class="small fw-semibold">Inventory</span>
        </a>
        <a href="{{ route('operator.products-for-sale.index') }}" class="operator-quick-tile text-center">
            <i class="fa-solid fa-ice-cream"></i>
            <span class="small fw-semibold">Store Menu</span>
        </a>
        <a href="{{ route('operator.pos.index') }}#dailyClosingModal" class="operator-quick-tile text-center">
            <i class="fa-solid fa-calendar-check"></i>
            <span class="small fw-semibold">Closing</span>
        </a>
</div>

<section class="mb-4">
    <p class="frosty-section-title mb-2"><i class="fa-solid fa-sun me-1"></i> Today at a glance</p>
    <div class="frosty-grid-glance">
            <div class="operator-glance-item">
                <div class="small text-muted">Today&apos;s sales</div>
                <div class="fs-5 fw-bold text-primary">₱{{ number_format($today['sales'], 2) }}</div>
            </div>
            <div class="operator-glance-item">
                <div class="small text-muted">Today&apos;s orders</div>
                <div class="fs-5 fw-bold">{{ $today['orders'] }}</div>
            </div>
            <div class="operator-glance-item">
                <div class="small text-muted">Today&apos;s profit</div>
                <div class="fs-5 fw-bold text-success">₱{{ number_format($today['profit'], 2) }}</div>
            </div>
            <div class="operator-glance-item">
                <div class="small text-muted">Inventory usage</div>
                <div class="fs-5 fw-bold">{{ $today['inventory_usage'] }} <span class="small fw-normal text-muted">units</span></div>
            </div>
    </div>
</section>

<div class="frosty-grid-stats mb-4">
        <a href="{{ $cards['orders_30']['url'] }}" class="operator-stat-card">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <span class="text-muted small fw-semibold"><i class="fa-solid fa-cart-shopping me-1"></i> Orders (30 days)</span>
                <i class="fa-solid fa-chevron-right text-muted"></i>
            </div>
            <div class="fs-3 fw-bold">{{ $cards['orders_30']['count'] }}</div>
            <div class="text-muted small">₱{{ number_format($cards['orders_30']['amount'], 2) }} total</div>
        </a>
        <a href="{{ $cards['pos_sales']['url'] }}" class="operator-stat-card">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <span class="text-muted small fw-semibold"><i class="fa-solid fa-cash-register me-1"></i> Sales summary (POS)</span>
                <i class="fa-solid fa-chevron-right text-muted"></i>
            </div>
            <div class="fs-3 fw-bold text-success">₱{{ number_format($cards['pos_sales']['today'], 2) }}</div>
            <div class="text-muted small">Today · ₱{{ number_format($cards['pos_sales']['month'], 2) }} this month</div>
        </a>
        <a href="{{ $cards['inventory_alerts']['url'] }}" class="operator-stat-card {{ $cards['inventory_alerts']['count'] > 0 ? 'border-warning' : '' }}">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <span class="text-muted small fw-semibold"><i class="fa-solid fa-triangle-exclamation me-1"></i> Inventory alerts</span>
                <i class="fa-solid fa-chevron-right text-muted"></i>
            </div>
            <div class="fs-3 fw-bold {{ $cards['inventory_alerts']['count'] > 0 ? 'text-warning' : 'text-success' }}">{{ $cards['inventory_alerts']['count'] }}</div>
            <div class="text-muted small">{{ $cards['inventory_alerts']['low'] }} low · {{ $cards['inventory_alerts']['out'] }} out</div>
        </a>
</div>

<div class="frosty-grid-stats mb-4">
    <div class="frosty-stat-card">
        <div class="text-muted small"><i class="fa-solid fa-wallet me-1"></i> Wallet</div>
        <div class="fs-4 fw-bold">₱{{ number_format($wallet->balance, 2) }}</div>
        <a href="{{ route('operator.wallet') }}" class="small stretched-link">View wallet</a>
    </div>
    <div class="frosty-stat-card">
        <div class="text-muted small"><i class="fa-solid fa-star me-1"></i> Points ({{ \App\Support\FrostySettings::currentMonth() }})</div>
        <div class="fs-4 fw-bold">{{ $qualification->personal_points ?? 0 }} / {{ $threshold }}</div>
        @if ($qualification?->qualified)
            <span class="badge text-bg-success">Qualified</span>
        @else
            <span class="badge text-bg-secondary">Not eligible</span>
        @endif
    </div>
    <div class="frosty-stat-card">
        <div class="text-muted small"><i class="fa-solid fa-lock me-1"></i> Daily closing</div>
        @if ($dayLocked)
            <div class="fw-semibold text-warning">Locked for today</div>
            <a href="{{ route('operator.pos.index') }}" class="small">View on POS</a>
        @else
            <div class="fw-semibold text-success">Open</div>
            <a href="{{ route('operator.pos.index') }}#dailyClosingModal" class="small">Submit closing</a>
        @endif
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-body fw-semibold d-flex justify-content-between align-items-center">
        <span><i class="fa-solid fa-clock-rotate-left me-1"></i> Recent orders</span>
        <a href="{{ route('operator.orders.index') }}" class="small">See all</a>
    </div>
    <ul class="list-group list-group-flush">
        @forelse ($recentOrders as $o)
            <li class="list-group-item d-flex justify-content-between">
                <span>#{{ $o->id }} · {{ $o->total_points }} pts</span>
                <span class="badge text-bg-light text-dark">{{ $o->status->value }}</span>
            </li>
        @empty
            <li class="list-group-item text-muted">No orders yet</li>
        @endforelse
    </ul>
</div>
@endsection
