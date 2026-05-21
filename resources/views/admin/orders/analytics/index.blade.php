@extends('layouts.app')
@section('title', 'Order Analytics')
@section('content')
@php
    $s = $analytics['summary'];
    $regional = $analytics['regionalStats'];
    $daily = $analytics['dailyTrends'];
    $monthly = $analytics['monthlyTrends'];
    $distributorStats = $analytics['distributorStats'];
    $operators = $analytics['operatorStats'];
    $products = $analytics['productStats'];
    $categories = $analytics['categoryStats'];
    $status = $analytics['statusStats'];
    $charts = $analytics['charts'];
@endphp

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h1 class="h3 mb-1">Order Analytics</h1>
        <p class="text-muted small mb-0">System-wide order volume, value, and performance across regions, distributors, and operators</p>
    </div>
    <div class="d-flex gap-2">
        @if (auth()->user()?->role === \App\Enums\UserRole::SuperAdmin || auth()->user()?->role === \App\Enums\UserRole::PurchasingAdmin)
            <a href="{{ route('admin.orders.index') }}" class="btn btn-outline-primary btn-sm">Order queue</a>
        @endif
        <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-secondary btn-sm">Admin</a>
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-header bg-white fw-semibold">Filters</div>
    <div class="card-body">
        <form method="get" action="{{ route('admin.orders.analytics') }}" class="row g-3 align-items-end">
            <div class="col-md-3 col-lg-2">
                <label class="form-label small">From</label>
                <input type="date" name="date_from" class="form-control form-control-sm" value="{{ $filters['date_from'] ?? '' }}">
            </div>
            <div class="col-md-3 col-lg-2">
                <label class="form-label small">To</label>
                <input type="date" name="date_to" class="form-control form-control-sm" value="{{ $filters['date_to'] ?? '' }}">
            </div>
            <div class="col-md-3 col-lg-2">
                <label class="form-label small">Region</label>
                <select name="region" class="form-select form-select-sm">
                    <option value="">All regions</option>
                    @foreach (['luzon' => 'Luzon', 'davao' => 'Davao', 'tacloban' => 'Tacloban'] as $val => $label)
                        <option value="{{ $val }}" @selected(($filters['region'] ?? '') === $val)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4 col-lg-3">
                <label class="form-label small">Distributor</label>
                <select name="distributor_id" class="form-select form-select-sm">
                    <option value="">All distributors</option>
                    @foreach ($filterDistributors as $d)
                        <option value="{{ $d->id }}" @selected((string) ($filters['distributor_id'] ?? '') === (string) $d->id)>{{ $d->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 col-lg-2">
                <label class="form-label small">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">All statuses</option>
                    @foreach (['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected'] as $val => $label)
                        <option value="{{ $val }}" @selected(($filters['status'] ?? '') === $val)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-auto d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm">Apply</button>
                <a href="{{ route('admin.orders.analytics') }}" class="btn btn-outline-secondary btn-sm">Reset</a>
            </div>
        </form>
        @if ($s['has_date_filter'])
            <p class="small text-muted mb-0 mt-2">Filtered period: {{ number_format($s['filtered_count']) }} orders · ₱{{ number_format($s['filtered_value'], 2) }} value</p>
        @endif
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-6 col-lg-4 col-xl">
        <div class="card border-0 shadow-sm h-100"><div class="card-body">
            <div class="text-muted small">Total orders (all time)</div>
            <div class="fs-3 fw-bold">{{ number_format($s['total_orders_all_time']) }}</div>
        </div></div>
    </div>
    <div class="col-md-6 col-lg-4 col-xl">
        <div class="card border-0 shadow-sm h-100"><div class="card-body">
            <div class="text-muted small">Orders (last 30 days)</div>
            <div class="fs-3 fw-bold">{{ number_format($s['total_orders_30_days']) }}</div>
        </div></div>
    </div>
    <div class="col-md-6 col-lg-4 col-xl">
        <div class="card border-0 shadow-sm h-100"><div class="card-body">
            <div class="text-muted small">Order value (30 days)</div>
            <div class="fs-3 fw-bold">₱{{ number_format($s['total_value_30_days'], 2) }}</div>
        </div></div>
    </div>
    <div class="col-md-6 col-lg-4 col-xl">
        <div class="card border-0 shadow-sm h-100"><div class="card-body">
            <div class="text-muted small">Avg order value (30 days)</div>
            <div class="fs-3 fw-bold">₱{{ number_format($s['avg_order_value_30_days'], 2) }}</div>
        </div></div>
    </div>
    <div class="col-md-6 col-lg-4 col-xl">
        <div class="card border-0 shadow-sm h-100"><div class="card-body">
            <div class="text-muted small">Softserve value (30 days)</div>
            <div class="fs-3 fw-bold">₱{{ number_format($s['softserve_value_30_days'], 2) }}</div>
        </div></div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-6">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white fw-semibold">Daily order count (30 days)</div>
            <div class="card-body"><canvas id="chart-daily-count" height="120"></canvas></div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white fw-semibold">Daily order value (30 days)</div>
            <div class="card-body"><canvas id="chart-daily-value" height="120"></canvas></div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-8">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white fw-semibold">Region comparison (order value)</div>
            <div class="card-body"><canvas id="chart-region" height="120"></canvas></div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white fw-semibold">Softserve vs non-softserve</div>
            <div class="card-body"><canvas id="chart-softserve" height="180"></canvas></div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-6">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white fw-semibold">Monthly order count (12 months)</div>
            <div class="card-body"><canvas id="chart-monthly-count" height="120"></canvas></div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white fw-semibold">Monthly order value (12 months)</div>
            <div class="card-body"><canvas id="chart-monthly-value" height="120"></canvas></div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white fw-semibold">Category demand (units)</div>
            <div class="card-body"><canvas id="chart-category" height="200"></canvas></div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white fw-semibold">Regional performance</div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead class="table-light"><tr><th>Region</th><th class="text-end">Orders</th><th class="text-end">Value</th><th class="text-end">Avg</th></tr></thead>
                    <tbody>
                    @foreach ($regional['table'] as $row)
                        <tr>
                            <td>{{ $row['region'] }}</td>
                            <td class="text-end">{{ number_format($row['orders']) }}</td>
                            <td class="text-end">₱{{ number_format($row['value'], 2) }}</td>
                            <td class="text-end">₱{{ number_format($row['avg'], 2) }}</td>
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
            <div class="card-header bg-white fw-semibold">Top distributors by order value</div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead class="table-light"><tr><th>Distributor</th><th>Region</th><th class="text-end">Orders</th><th class="text-end">Value</th></tr></thead>
                    <tbody>
                    @forelse ($distributorStats['table'] as $row)
                        <tr>
                            <td>{{ $row['name'] }}</td>
                            <td>{{ $row['region'] }}</td>
                            <td class="text-end">{{ number_format($row['orders']) }}</td>
                            <td class="text-end">₱{{ number_format($row['value'], 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-muted text-center py-3">No orders in this period</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white fw-semibold">Top operators by order value</div>
            <div class="table-responsive" style="max-height:320px">
                <table class="table table-sm mb-0">
                    <thead class="table-light"><tr><th>Operator</th><th class="text-end">Orders</th><th class="text-end">Value</th></tr></thead>
                    <tbody>
                    @forelse ($operators['top'] as $row)
                        <tr>
                            <td>{{ $row->name }}</td>
                            <td class="text-end">{{ number_format($row->order_count) }}</td>
                            <td class="text-end">₱{{ number_format($row->order_value, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-muted text-center py-3">No operator orders</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-footer bg-white small text-muted">
                Avg orders per operator (30 days): <strong>{{ $operators['avg_orders_per_operator_30d'] }}</strong>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white fw-semibold">Operators with no orders (30 days)</div>
            <ul class="list-group list-group-flush small">
            @forelse ($operators['inactive'] as $op)
                <li class="list-group-item">{{ $op->name }}</li>
            @empty
                <li class="list-group-item text-muted">All operators have recent orders</li>
            @endforelse
            </ul>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white fw-semibold">Top products by quantity</div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead class="table-light"><tr><th>Product</th><th class="text-end">Qty</th></tr></thead>
                    <tbody>
                    @forelse ($products['byQty'] as $row)
                        <tr><td>{{ $row->name }}</td><td class="text-end">{{ number_format($row->units) }}</td></tr>
                    @empty
                        <tr><td colspan="2" class="text-muted text-center py-3">No data</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white fw-semibold">Top products by value</div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead class="table-light"><tr><th>Product</th><th class="text-end">Value</th></tr></thead>
                    <tbody>
                    @forelse ($products['byValue'] as $row)
                        <tr><td>{{ $row->name }}</td><td class="text-end">₱{{ number_format($row->revenue, 2) }}</td></tr>
                    @empty
                        <tr><td colspan="2" class="text-muted text-center py-3">No data</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-6">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white fw-semibold">Category breakdown</div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead class="table-light"><tr><th>Category</th><th class="text-end">Units</th><th class="text-end">Revenue</th></tr></thead>
                    <tbody>
                    @foreach ($categories['breakdown'] as $row)
                        <tr>
                            <td>{{ $row['category'] }}</td>
                            <td class="text-end">{{ number_format($row['units']) }}</td>
                            <td class="text-end">₱{{ number_format($row['revenue'], 2) }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white fw-semibold">Order status analysis</div>
            <div class="card-body">
                <div class="row g-3 mb-3">
                    <div class="col-4 text-center">
                        <div class="text-muted small">Pending</div>
                        <div class="fs-4 fw-bold">{{ number_format($status['counts']['pending']) }}</div>
                    </div>
                    <div class="col-4 text-center">
                        <div class="text-muted small">Approved</div>
                        <div class="fs-4 fw-bold text-success">{{ number_format($status['counts']['approved']) }}</div>
                    </div>
                    <div class="col-4 text-center">
                        <div class="text-muted small">Rejected</div>
                        <div class="fs-4 fw-bold text-danger">{{ number_format($status['counts']['rejected']) }}</div>
                    </div>
                </div>
                <p class="small mb-1"><strong>Approval rate</strong> (approved vs pending+approved): {{ $status['conversion_rate'] }}%</p>
                <p class="small mb-1"><strong>Rejection rate</strong>: {{ $status['rejection_rate'] }}%</p>
                <p class="small text-muted mb-0">{{ $status['note'] }}</p>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
const chartData = @json($charts);

function lineChart(id, labels, values, label, color) {
    new Chart(document.getElementById(id), {
        type: 'line',
        data: {
            labels,
            datasets: [{ label, data: values, borderColor: color, tension: 0.25, fill: false }],
        },
        options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } },
    });
}

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

lineChart('chart-daily-count', chartData.dailyCount.labels, chartData.dailyCount.values, 'Orders', '#0d6efd');
lineChart('chart-daily-value', chartData.dailyValue.labels, chartData.dailyValue.values, 'Value', '#198754');
barChart('chart-region', chartData.regionComparison.labels, chartData.regionComparison.orderValues, 'Order value');
barChart('chart-softserve', chartData.softserveBar.labels, chartData.softserveBar.values, 'Value');
lineChart('chart-monthly-count', chartData.monthlyCount.labels, chartData.monthlyCount.values, 'Orders', '#6610f2');
lineChart('chart-monthly-value', chartData.monthlyValue.labels, chartData.monthlyValue.values, 'Value', '#fd7e14');
pieChart('chart-category', chartData.categoryPie.labels, chartData.categoryPie.values);
</script>
@endsection
