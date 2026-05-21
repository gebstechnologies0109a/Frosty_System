@extends('layouts.app')
@section('title', 'Purchasing Analytics')
@section('content')
@php
    $s = $analytics['summary'];
    $inv = $analytics['inventoryHealth'];
    $perf = $analytics['productPerformance'];
    $pricing = $analytics['pricing'];
    $orders = $analytics['orderTrends'];
    $invVal = $analytics['inventoryValue'];
    $charts = $analytics['charts'];
@endphp

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h1 class="h3 mb-1">Purchasing Analytics</h1>
        <p class="text-muted small mb-0">Real-time product, inventory, pricing, and order insights</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.purchasing.stock-movements.index') }}" class="btn btn-outline-secondary btn-sm">Stock logs</a>
        <a href="{{ route('admin.purchasing.products.index') }}" class="btn btn-outline-primary btn-sm">Product catalog</a>
    </div>
</div>

{{-- Recent stock movements --}}
<div class="card shadow-sm mb-4">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <span class="fw-semibold">Recent stock movements</span>
        <a href="{{ route('admin.purchasing.stock-movements.index') }}" class="small">View all</a>
    </div>
    <div class="table-responsive">
        <table class="table table-sm mb-0">
            <thead class="table-light">
                <tr>
                    <th>Time</th>
                    <th>Product</th>
                    <th>Action</th>
                    <th class="text-end">Change</th>
                    <th class="text-end">After</th>
                </tr>
            </thead>
            <tbody>
            @forelse ($analytics['recentStockMovements'] as $m)
                <tr>
                    <td class="small text-nowrap">{{ $m->created_at->diffForHumans() }}</td>
                    <td>{{ $m->product?->name ?? '—' }}</td>
                    <td><span class="badge text-bg-secondary">{{ $m->actionLabel() }}</span></td>
                    <td class="text-end {{ $m->quantity_change >= 0 ? 'text-success' : 'text-danger' }}">
                        {{ $m->quantity_change > 0 ? '+' : '' }}{{ $m->quantity_change }}
                    </td>
                    <td class="text-end">{{ $m->stock_after }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-muted text-center py-3">No movements logged yet</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- 1. Summary cards --}}
<div class="row g-3 mb-4">
    <div class="col-md-6 col-lg-4 col-xl">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-body">
                <div class="text-muted small">Active products</div>
                <div class="fs-3 fw-bold">{{ number_format($s['active_products']) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-4 col-xl">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-body">
                <div class="text-muted small">Softserve products</div>
                <div class="fs-3 fw-bold">{{ number_format($s['softserve_products']) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-4 col-xl">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-body">
                <div class="text-muted small">Supply / spare parts</div>
                <div class="fs-3 fw-bold">{{ number_format($s['supply_sparepart_products']) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-4 col-xl">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-body">
                <div class="text-muted small">Inventory value (Luzon)</div>
                <div class="fs-3 fw-bold">₱{{ number_format($s['inventory_value_luzon'], 2) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-4 col-xl">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-body">
                <div class="text-muted small">Orders (last 30 days)</div>
                <div class="fs-3 fw-bold">{{ number_format($s['orders_last_30_days']) }}</div>
            </div>
        </div>
    </div>
</div>

{{-- Charts row 1 --}}
<div class="row g-4 mb-4">
    <div class="col-lg-6">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white fw-semibold">Inventory by category</div>
            <div class="card-body"><canvas id="chart-inventory-category" height="220"></canvas></div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white fw-semibold">Category sales breakdown (30 days)</div>
            <div class="card-body"><canvas id="chart-category-sales" height="220"></canvas></div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-8">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white fw-semibold">Daily order volume (last 30 days)</div>
            <div class="card-body"><canvas id="chart-daily-orders" height="120"></canvas></div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white fw-semibold">Softserve vs non-softserve (30 days)</div>
            <div class="card-body"><canvas id="chart-softserve" height="200"></canvas></div>
        </div>
    </div>
</div>

{{-- 2. Inventory health --}}
<div class="card shadow-sm mb-4">
    <div class="card-header bg-white fw-semibold">Inventory health</div>
    <div class="card-body">
        <div class="row g-4">
            <div class="col-lg-4">
                <h6 class="text-warning">Low stock (&lt; 10)</h6>
                <div class="table-responsive" style="max-height:220px">
                    <table class="table table-sm table-striped mb-0">
                        <thead><tr><th>Product</th><th>Stock</th></tr></thead>
                        <tbody>
                        @forelse ($inv['lowStock'] as $row)
                            <tr><td>{{ $row['name'] }}</td><td>{{ $row['stock'] }}</td></tr>
                        @empty
                            <tr><td colspan="2" class="text-muted">None</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="col-lg-4">
                <h6 class="text-danger">Out of stock</h6>
                <div class="table-responsive" style="max-height:220px">
                    <table class="table table-sm table-striped mb-0">
                        <thead><tr><th>Product</th><th>Category</th></tr></thead>
                        <tbody>
                        @forelse ($inv['outOfStock'] as $row)
                            <tr><td>{{ $row['name'] }}</td><td>{{ $row['category'] }}</td></tr>
                        @empty
                            <tr><td colspan="2" class="text-muted">None</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="col-lg-4">
                <h6 class="text-info">Over-stocked (&gt; 500)</h6>
                <div class="table-responsive" style="max-height:220px">
                    <table class="table table-sm table-striped mb-0">
                        <thead><tr><th>Product</th><th>Stock</th></tr></thead>
                        <tbody>
                        @forelse ($inv['overStocked'] as $row)
                            <tr><td>{{ $row['name'] }}</td><td>{{ $row['stock'] }}</td></tr>
                        @empty
                            <tr><td colspan="2" class="text-muted">None</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- 3. Product performance --}}
<div class="row g-4 mb-4">
    <div class="col-lg-6">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white fw-semibold">Top 10 best sellers (30 days)</div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead class="table-light"><tr><th>Product</th><th>Category</th><th class="text-end">Units</th><th class="text-end">Revenue</th></tr></thead>
                    <tbody>
                    @forelse ($perf['bestSellers'] as $row)
                        <tr>
                            <td>{{ $row->name }}</td>
                            <td>{{ \App\Enums\ProductCategory::tryFrom($row->category)?->label() ?? $row->category }}</td>
                            <td class="text-end">{{ number_format($row->units_sold) }}</td>
                            <td class="text-end">₱{{ number_format($row->revenue, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-muted text-center">No approved sales in the last 30 days</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white fw-semibold">Top 10 slow-moving (no sales 60 days)</div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead class="table-light"><tr><th>Product</th><th>Category</th><th class="text-end">Stock</th></tr></thead>
                    <tbody>
                    @forelse ($perf['slowMoving'] as $row)
                        <tr>
                            <td>{{ $row['name'] }}</td>
                            <td>{{ $row['category'] }}</td>
                            <td class="text-end">{{ $row['stock'] }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-muted text-center">All active products had recent sales</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- 4. Regional pricing --}}
<div class="card shadow-sm mb-4">
    <div class="card-header bg-white fw-semibold">Regional pricing insights</div>
    <div class="card-body">
        @if ($pricing['alerts']->isNotEmpty())
            <div class="alert alert-warning py-2 small">
                <strong>Price gap alerts (&gt;10% above Luzon):</strong>
                {{ $pricing['alerts']->pluck('name')->take(8)->join(', ') }}
                @if ($pricing['alerts']->count() > 8) … +{{ $pricing['alerts']->count() - 8 }} more @endif
            </div>
        @endif
        <div class="table-responsive" style="max-height:360px">
            <table class="table table-sm table-hover mb-0">
                <thead class="table-light sticky-top">
                    <tr>
                        <th>Product</th>
                        <th>Category</th>
                        <th class="text-end">Luzon</th>
                        <th class="text-end">Davao</th>
                        <th class="text-end">Tacloban</th>
                        <th class="text-end">Davao gap %</th>
                        <th class="text-end">Tacloban gap %</th>
                    </tr>
                </thead>
                <tbody>
                @foreach ($pricing['comparison']->take(100) as $row)
                    <tr class="{{ ($row['davao_alert'] || $row['tacloban_alert']) ? 'table-warning' : '' }}">
                        <td>{{ $row['name'] }}</td>
                        <td>{{ $row['category'] }}</td>
                        <td class="text-end">₱{{ number_format($row['luzon'], 2) }}</td>
                        <td class="text-end">₱{{ number_format($row['davao'], 2) }}</td>
                        <td class="text-end">₱{{ number_format($row['tacloban'], 2) }}</td>
                        <td class="text-end {{ $row['davao_alert'] ? 'text-danger fw-semibold' : '' }}">{{ $row['davao_gap_pct'] }}%</td>
                        <td class="text-end {{ $row['tacloban_alert'] ? 'text-danger fw-semibold' : '' }}">{{ $row['tacloban_gap_pct'] }}%</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <p class="small text-muted mt-2 mb-0">Showing first 100 active products. Highlighted rows exceed Luzon by more than 10%.</p>
    </div>
</div>

{{-- 5. Order trends --}}
<div class="row g-4 mb-4">
    <div class="col-lg-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white fw-semibold">Orders by region (30 days)</div>
            <div class="card-body">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between"><span>Luzon</span><strong>{{ $orders['regionDistribution']['luzon'] }}</strong></li>
                    <li class="list-group-item d-flex justify-content-between"><span>Davao</span><strong>{{ $orders['regionDistribution']['davao'] }}</strong></li>
                    <li class="list-group-item d-flex justify-content-between"><span>Tacloban</span><strong>{{ $orders['regionDistribution']['tacloban'] }}</strong></li>
                </ul>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white fw-semibold">Monthly order volume (last 12 months)</div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead class="table-light"><tr><th>Month</th><th class="text-end">Orders</th></tr></thead>
                    <tbody>
                    @forelse ($orders['monthly'] as $row)
                        <tr><td>{{ $row->month }}</td><td class="text-end">{{ $row->total }}</td></tr>
                    @empty
                        <tr><td colspan="2" class="text-muted">No orders yet</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- 6. Category analytics --}}
<div class="card shadow-sm mb-4">
    <div class="card-header bg-white fw-semibold">Category-level analytics</div>
    <div class="table-responsive">
        <table class="table table-sm table-striped mb-0">
            <thead class="table-light">
                <tr>
                    <th>Category</th>
                    <th class="text-end">Products</th>
                    <th class="text-end">Total stock</th>
                    <th class="text-end">Sales (30d)</th>
                    <th class="text-end">Avg Luzon ₱</th>
                    <th>Fastest moving</th>
                    <th>Slowest (by name)</th>
                </tr>
            </thead>
            <tbody>
            @foreach ($analytics['categoryAnalytics'] as $cat)
                <tr>
                    <td>{{ $cat['label'] }}</td>
                    <td class="text-end">{{ $cat['total_products'] }}</td>
                    <td class="text-end">{{ number_format($cat['total_stock']) }}</td>
                    <td class="text-end">{{ number_format($cat['total_sales_30d']) }}</td>
                    <td class="text-end">₱{{ number_format($cat['avg_price_luzon'], 2) }}</td>
                    <td>{{ $cat['fastest_moving'] }}</td>
                    <td>{{ $cat['slowest_moving'] }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>

{{-- 7. Inventory value --}}
<div class="row g-4 mb-4">
    <div class="col-lg-6">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white fw-semibold">Inventory value by category (Luzon)</div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead class="table-light"><tr><th>Category</th><th class="text-end">Value</th></tr></thead>
                    <tbody>
                    @foreach ($invVal['byCategory'] as $row)
                        <tr>
                            <td>{{ \App\Enums\ProductCategory::tryFrom($row->category)?->label() ?? $row->category }}</td>
                            <td class="text-end">₱{{ number_format($row->value, 2) }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white fw-semibold">Inventory value by region</div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead class="table-light"><tr><th>Region</th><th class="text-end">Value</th></tr></thead>
                    <tbody>
                    @foreach ($invVal['byRegion'] as $row)
                        <tr>
                            <td>{{ $row['region'] }}</td>
                            <td class="text-end">₱{{ number_format($row['value'], 2) }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-6">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white fw-semibold">Highest value items (top 10)</div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead class="table-light"><tr><th>Product</th><th class="text-end">Stock</th><th class="text-end">Value</th></tr></thead>
                    <tbody>
                    @foreach ($invVal['highest'] as $row)
                        <tr>
                            <td>{{ $row->name }}</td>
                            <td class="text-end">{{ $row->stock }}</td>
                            <td class="text-end">₱{{ number_format($row->item_value, 2) }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white fw-semibold">Lowest value items (bottom 10)</div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead class="table-light"><tr><th>Product</th><th class="text-end">Stock</th><th class="text-end">Value</th></tr></thead>
                    <tbody>
                    @foreach ($invVal['lowest'] as $row)
                        <tr>
                            <td>{{ $row->name }}</td>
                            <td class="text-end">{{ $row->stock }}</td>
                            <td class="text-end">₱{{ number_format($row->item_value, 2) }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
const chartData = @json($charts);

function barChart(id, labels, values, label) {
    new Chart(document.getElementById(id), {
        type: 'bar',
        data: {
            labels,
            datasets: [{ label, data: values, backgroundColor: 'rgba(13, 110, 253, 0.65)' }],
        },
        options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } },
    });
}

function lineChart(id, labels, values) {
    new Chart(document.getElementById(id), {
        type: 'line',
        data: {
            labels,
            datasets: [{ label: 'Orders', data: values, borderColor: '#0d6efd', tension: 0.25, fill: false }],
        },
        options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } },
    });
}

function pieChart(id, labels, values) {
    new Chart(document.getElementById(id), {
        type: 'pie',
        data: {
            labels,
            datasets: [{ data: values, backgroundColor: ['#0d6efd','#6610f2','#6f42c1','#d63384','#fd7e14','#ffc107','#198754','#20c997','#0dcaf0','#6c757d'] }],
        },
        options: { responsive: true },
    });
}

barChart('chart-inventory-category', chartData.inventoryByCategory.labels, chartData.inventoryByCategory.values, 'Stock');
pieChart('chart-category-sales', chartData.categorySales.labels, chartData.categorySales.values);
lineChart('chart-daily-orders', chartData.dailyOrders.labels, chartData.dailyOrders.values);
barChart('chart-softserve', chartData.softserveVsOther.labels, chartData.softserveVsOther.values, 'Units sold');
</script>
@endsection
