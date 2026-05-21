@extends('layouts.app')
@section('title', 'Distributor Dashboard')

@section('content')
@php
    $badgeClass = match ($pricingRegion->value) {
        'mindanao' => 'text-bg-info',
        default => 'text-bg-primary',
    };
@endphp

<div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1">{{ $distributor->name }}</h1>
        <p class="text-muted mb-2">Distributor dashboard — operator network &amp; orders</p>
        <span class="badge {{ $badgeClass }}">
            <i class="fa-solid fa-map-location-dot me-1"></i>
            Regional pricing: {{ $pricingRegion->label() }} ({{ $priceRegion->value }})
        </span>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('distributor.orders.create') }}" class="btn btn-primary btn-sm">
            <i class="fa-solid fa-truck me-1"></i> Order from Main
        </a>
        <a href="{{ route('distributor.orders.index') }}" class="btn btn-outline-primary btn-sm">Orders</a>
        <a href="{{ route('distributor.inventory.index') }}" class="btn btn-outline-secondary btn-sm">Inventory</a>
        <a href="{{ route('distributor.analytics') }}" class="btn btn-outline-secondary btn-sm">Analytics</a>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-md-4 col-xl">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Operators</div>
                <div class="fs-3 fw-bold">{{ number_format($summary['operators']) }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Pending approvals</div>
                <div class="fs-3 fw-bold text-warning">{{ number_format($summary['pending_orders']) }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Operator orders (30d)</div>
                <div class="fs-3 fw-bold">{{ number_format($summary['orders_30']) }}</div>
                <div class="text-muted small">₱{{ number_format($summary['order_value_30'], 2) }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Inventory alerts</div>
                <div class="fs-3 fw-bold {{ ($summary['low_stock_skus'] + $summary['out_of_stock_skus']) > 0 ? 'text-danger' : 'text-success' }}">
                    {{ $summary['low_stock_skus'] + $summary['out_of_stock_skus'] }}
                </div>
                <div class="text-muted small">{{ $summary['low_stock_skus'] }} low · {{ $summary['out_of_stock_skus'] }} out</div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white fw-semibold">Approved operator orders (14 days)</div>
            <div class="card-body">
                <canvas id="chart-distributor-orders" height="120"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white fw-semibold">Regional catalog ({{ $priceRegion->value }})</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Product</th>
                                <th class="text-end">Price</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($regionalProducts as $product)
                                <tr>
                                    <td>
                                        <span class="d-block">{{ $product['name'] }}</span>
                                        <span class="text-muted small">{{ $product['category'] }}</span>
                                    </td>
                                    <td class="text-end">₱{{ number_format($product['price'], 2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="2" class="text-muted p-3">No products priced for this region.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <span class="fw-semibold">Operators</span>
                <span class="badge text-bg-secondary">{{ $operators->count() }}</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Name</th>
                                <th>Region</th>
                                <th class="text-end">Orders (30d)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($operators as $operator)
                                <tr>
                                    <td>
                                        <span class="fw-semibold">{{ $operator->displayName() }}</span>
                                        <div class="text-muted small">{{ $operator->email }}</div>
                                    </td>
                                    <td><span class="badge text-bg-light text-dark">{{ $operator->region?->value ?? '—' }}</span></td>
                                    <td class="text-end">{{ $operator->orders_30_count }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="text-muted p-3">No operators assigned yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold">Pending operator orders</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Order</th>
                                <th>Operator</th>
                                <th class="text-end">Points</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($pendingOrders as $order)
                                <tr>
                                    <td>#{{ $order->id }}</td>
                                    <td>{{ $order->user?->name }}</td>
                                    <td class="text-end">{{ $order->total_points }}</td>
                                    <td class="text-end">
                                        @include('partials.distributor-order-approve', ['order' => $order])
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-muted p-3">No pending orders.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="card-footer bg-white">
                    <a href="{{ route('distributor.orders.index') }}" class="small">View all orders →</a>
                </div>
            </div>
        </div>
    </div>
</div>

@if ($inventorySummary['rows']->isNotEmpty())
<div class="card border-0 shadow-sm mt-4">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <span class="fw-semibold">Operator supplies inventory (lowest stock)</span>
        <a href="{{ route('distributor.inventory.index') }}" class="btn btn-sm btn-outline-primary">Full inventory</a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Operator</th>
                        <th>Product</th>
                        <th class="text-end">Stock</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($inventorySummary['rows'] as $row)
                        <tr>
                            <td>{{ $row['operator'] }}</td>
                            <td>{{ $row['product'] }}</td>
                            <td class="text-end">{{ $row['stock'] }}</td>
                            <td>
                                <span class="badge {{ $row['status_key'] === 'in_stock' ? 'text-bg-success' : ($row['status_key'] === 'low_stock' ? 'text-bg-warning' : 'text-bg-danger') }}">
                                    {{ $row['status'] }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif

@if ($myOrders->isNotEmpty())
<div class="card border-0 shadow-sm mt-4">
    <div class="card-header bg-white fw-semibold">My orders to Main</div>
    <ul class="list-group list-group-flush">
        @foreach ($myOrders as $order)
            <li class="list-group-item d-flex justify-content-between">
                <span>#{{ $order->id }} — {{ $order->status->value }}</span>
                <span>₱{{ number_format($order->total_amount, 2) }}</span>
            </li>
        @endforeach
    </ul>
</div>
@endif

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
const chartData = @json($chart);
if (chartData.labels.length && document.getElementById('chart-distributor-orders')) {
    new Chart(document.getElementById('chart-distributor-orders'), {
        type: 'line',
        data: {
            labels: chartData.labels,
            datasets: [{
                label: 'Approved orders',
                data: chartData.values,
                borderColor: '#0d6efd',
                tension: 0.25,
                fill: false,
            }],
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, ticks: { precision: 0 } } },
        },
    });
}
</script>
@endsection
