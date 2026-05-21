@extends('layouts.app')
@section('title', 'Distributor Analytics')
@section('content')
@php
    $s = $analytics['summary'];
    $ops = $analytics['operatorPerformance'];
    $orders = $analytics['orderTrends'];
    $products = $analytics['productPerformance'];
    $inv = $analytics['inventory'];
    $gen = $analytics['genealogy'];
    $pricing = $analytics['pricing'];
    $charts = $analytics['charts'];
@endphp

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h1 class="h3 mb-1">{{ $distributor->name }} — Analytics</h1>
        <p class="text-muted small mb-0">Operator network, orders, and demand insights (order value only — not rebates)</p>
    </div>
    <a href="{{ route('distributor.dashboard') }}" class="btn btn-outline-primary btn-sm">Dashboard</a>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-6 col-lg-4 col-xl">
        <div class="card border-0 shadow-sm h-100"><div class="card-body">
            <div class="text-muted small">Operators</div>
            <div class="fs-3 fw-bold">{{ number_format($s['total_operators']) }}</div>
        </div></div>
    </div>
    <div class="col-md-6 col-lg-4 col-xl">
        <div class="card border-0 shadow-sm h-100"><div class="card-body">
            <div class="text-muted small">Orders (30 days)</div>
            <div class="fs-3 fw-bold">{{ number_format($s['orders_last_30_days']) }}</div>
        </div></div>
    </div>
    <div class="col-md-6 col-lg-4 col-xl">
        <div class="card border-0 shadow-sm h-100"><div class="card-body">
            <div class="text-muted small">Order value (30 days)</div>
            <div class="fs-3 fw-bold">₱{{ number_format($s['order_value_last_30_days'], 2) }}</div>
        </div></div>
    </div>
    <div class="col-md-6 col-lg-4 col-xl">
        <div class="card border-0 shadow-sm h-100"><div class="card-body">
            <div class="text-muted small">Softserve orders (30 days)</div>
            <div class="fs-3 fw-bold">{{ number_format($s['softserve_orders_last_30_days']) }}</div>
        </div></div>
    </div>
    <div class="col-md-6 col-lg-4 col-xl">
        <div class="card border-0 shadow-sm h-100"><div class="card-body">
            <div class="text-muted small">Fastest-growing level</div>
            <div class="fs-3 fw-bold">L{{ $s['fastest_growing_level']['level'] }}</div>
            <div class="small text-muted">+{{ $s['fastest_growing_level']['count'] }} this month</div>
        </div></div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-8">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white fw-semibold">Daily order volume (30 days)</div>
            <div class="card-body"><canvas id="chart-daily-orders" height="100"></canvas></div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white fw-semibold">Orders in your region ({{ $pricing['distributorRegion'] }})</div>
            <div class="card-body">
                <div class="fs-2 fw-bold">{{ $orders['regionOrders'] }}</div>
                <p class="small text-muted mb-0">of {{ $orders['totalOrders'] }} operator orders used {{ $analytics['region'] }} pricing</p>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-6">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white fw-semibold">Monthly order volume (12 months)</div>
            <div class="card-body"><canvas id="chart-monthly-orders" height="120"></canvas></div>
        </div>
    </div>
    <div class="col-lg-3">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white fw-semibold">Category breakdown</div>
            <div class="card-body"><canvas id="chart-category" height="180"></canvas></div>
        </div>
    </div>
    <div class="col-lg-3">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white fw-semibold">Softserve demand</div>
            <div class="card-body"><canvas id="chart-softserve" height="180"></canvas></div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-6">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white fw-semibold">Top operators by order volume</div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead class="table-light"><tr><th>Operator</th><th class="text-end">Orders</th><th class="text-end">Value</th></tr></thead>
                    <tbody>
                    @forelse ($ops['topOperators'] as $row)
                        <tr>
                            <td>{{ $row['name'] }}</td>
                            <td class="text-end">{{ $row['orders'] }}</td>
                            <td class="text-end">₱{{ number_format($row['value'], 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-muted text-center">No operator orders yet</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white fw-semibold">Operator status</div>
            <div class="row g-0">
                <div class="col-md-6 border-end">
                    <div class="p-3">
                        <h6 class="small text-muted">No orders (30 days)</h6>
                        <ul class="list-unstyled small mb-0">
                        @forelse ($ops['inactiveOperators'] as $u)
                            <li>{{ $u->name }}</li>
                        @empty
                            <li class="text-muted">All active</li>
                        @endforelse
                        </ul>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="p-3 border-bottom">
                        <h6 class="small text-warning">Nearing qualification (15–19 pts)</h6>
                        <ul class="list-unstyled small mb-0">
                        @forelse ($ops['nearingQualification'] as $u)
                            <li>{{ $u['name'] }} — {{ $u['points'] }} pts</li>
                        @empty
                            <li class="text-muted">None</li>
                        @endforelse
                        </ul>
                    </div>
                    <div class="p-3">
                        <h6 class="small text-success">Qualified this month</h6>
                        <ul class="list-unstyled small mb-0">
                        @forelse ($ops['qualifiedThisMonth'] as $u)
                            <li>{{ $u['name'] }} — {{ $u['points'] }} pts</li>
                        @empty
                            <li class="text-muted">None yet</li>
                        @endforelse
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-6">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white fw-semibold">Top ordered products (30 days)</div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead class="table-light"><tr><th>Product</th><th class="text-end">Units</th><th class="text-end">Revenue</th></tr></thead>
                    <tbody>
                    @forelse ($products['topProducts'] as $row)
                        <tr>
                            <td>{{ $row->name }}</td>
                            <td class="text-end">{{ number_format($row->units) }}</td>
                            <td class="text-end">₱{{ number_format($row->revenue, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-muted text-center">No approved orders</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white fw-semibold">Slow-moving (no orders 60 days)</div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead class="table-light"><tr><th>Product</th><th>Category</th></tr></thead>
                    <tbody>
                    @forelse ($products['slowMoving'] as $row)
                        <tr>
                            <td>{{ $row->name }}</td>
                            <td>{{ \App\Enums\ProductCategory::tryFrom($row->category)?->label() ?? $row->category }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="2" class="text-muted text-center">All products had recent demand</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-header bg-white fw-semibold">Inventory recommendations</div>
    <div class="card-body">
        <div class="row g-4">
            <div class="col-lg-7">
                <h6 class="small text-muted">Frequently ordered by your operators (30 days)</h6>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead class="table-light"><tr><th>Product</th><th class="text-end">Ordered</th><th class="text-end">Suggested reorder</th><th class="text-end">Global stock</th></tr></thead>
                        <tbody>
                        @forelse ($inv['frequent'] as $row)
                            <tr>
                                <td>{{ $row['name'] }}</td>
                                <td class="text-end">{{ $row['units_ordered'] }}</td>
                                <td class="text-end fw-semibold">{{ $row['suggested_reorder'] }}</td>
                                <td class="text-end">{{ $row['current_stock'] }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-muted">No order data</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="col-lg-5">
                <h6 class="small text-warning">Global low stock (&lt; 10)</h6>
                <ul class="list-unstyled small">
                @forelse ($inv['lowStock'] as $row)
                    <li>{{ $row->name }} — {{ $row->stock }}</li>
                @empty
                    <li class="text-muted">None</li>
                @endforelse
                </ul>
                <h6 class="small text-info mt-3">Global over-stocked (&gt; 500)</h6>
                <ul class="list-unstyled small">
                @forelse ($inv['overStocked'] as $row)
                    <li>{{ $row->name }} — {{ $row->stock }}</li>
                @empty
                    <li class="text-muted">None</li>
                @endforelse
                </ul>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-5">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white fw-semibold">Genealogy growth</div>
            <div class="card-body">
                <p class="mb-2"><strong>{{ $gen['newThisMonth'] }}</strong> new operators this month</p>
                <table class="table table-sm">
                    <thead class="table-light"><tr><th>Level</th><th class="text-end">Operators</th></tr></thead>
                    <tbody>
                    @for ($l = 1; $l <= 4; $l++)
                        <tr><td>Level {{ $l }}</td><td class="text-end">{{ $gen['perLevel'][$l] ?? 0 }}</td></tr>
                    @endfor
                    </tbody>
                </table>
                <h6 class="small text-muted mt-3">Most active branches</h6>
                <ul class="list-unstyled small mb-0">
                @forelse ($gen['activeBranches'] as $b)
                    <li>{{ $b['name'] }} — {{ $b['referrals'] }} referral(s)</li>
                @empty
                    <li class="text-muted">No referred operators yet</li>
                @endforelse
                </ul>
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white fw-semibold">New operators (last 6 months)</div>
            <div class="card-body"><canvas id="chart-genealogy" height="120"></canvas></div>
        </div>
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-header bg-white fw-semibold">Regional pricing impact ({{ $pricing['distributorRegion'] }} vs Luzon)</div>
    <div class="card-body">
        <div class="row mb-3">
            <div class="col-md-4"><span class="text-muted small">Avg order value (all)</span><div class="fw-bold">₱{{ number_format($pricing['avgOrderValue'], 2) }}</div></div>
            <div class="col-md-4"><span class="text-muted small">Avg order value ({{ $pricing['distributorRegion'] }})</span><div class="fw-bold">₱{{ number_format($pricing['avgRegionOrderValue'], 2) }}</div></div>
            <div class="col-md-4"><span class="text-muted small">Orders at your region price</span><div class="fw-bold">{{ $pricing['regionOrderCount'] }}</div></div>
        </div>
        <h6 class="small text-muted">Highest regional price difference vs Luzon</h6>
        <div class="table-responsive">
            <table class="table table-sm mb-0">
                <thead class="table-light"><tr><th>Product</th><th class="text-end">Luzon</th><th class="text-end">{{ $pricing['distributorRegion'] }}</th><th class="text-end">Diff</th></tr></thead>
                <tbody>
                @forelse ($pricing['topPriceDifferences'] as $row)
                    <tr>
                        <td>{{ $row->name }}</td>
                        <td class="text-end">₱{{ number_format($row->luzon, 2) }}</td>
                        <td class="text-end">₱{{ number_format($row->regional, 2) }}</td>
                        <td class="text-end text-danger">+₱{{ number_format($row->diff, 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-muted">No regional premiums</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
const charts = @json($charts);

function lineChart(id, labels, values, label) {
    new Chart(document.getElementById(id), {
        type: 'line',
        data: { labels, datasets: [{ label, data: values, borderColor: '#0d6efd', tension: 0.25, fill: false }] },
        options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } },
    });
}

function barChart(id, labels, values) {
    new Chart(document.getElementById(id), {
        type: 'bar',
        data: { labels, datasets: [{ data: values, backgroundColor: 'rgba(13, 110, 253, 0.65)' }] },
        options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } },
    });
}

function pieChart(id, labels, values) {
    new Chart(document.getElementById(id), {
        type: 'pie',
        data: { labels, datasets: [{ data: values, backgroundColor: ['#0d6efd','#6610f2','#6f42c1','#fd7e14','#198754','#ffc107','#20c997','#dc3545'] }] },
        options: { responsive: true },
    });
}

lineChart('chart-daily-orders', charts.dailyOrders.labels, charts.dailyOrders.values, 'Orders');
lineChart('chart-monthly-orders', charts.monthlyOrders.labels, charts.monthlyOrders.values, 'Orders');
lineChart('chart-genealogy', charts.genealogyGrowth.labels, charts.genealogyGrowth.values, 'New operators');
pieChart('chart-category', charts.categoryBreakdown.labels, charts.categoryBreakdown.values);
barChart('chart-softserve', charts.softserveVsOther.labels, charts.softserveVsOther.values);
</script>
@endsection
