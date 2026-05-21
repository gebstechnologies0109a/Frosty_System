@extends('layouts.operator')
@section('header_title', 'Analytics')
@section('title', 'Operator Analytics')
@section('content')
@php
    $s = $analytics['summary'];
    $charts = $analytics['charts'];
    $rebates = $analytics['rebatesEnabled'];
    $l14 = $level1to4Report;
    $l04 = $level0to4Report;
@endphp

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h1 class="h3 mb-1">Operator Analytics</h1>
        <p class="text-muted small mb-0">{{ $operator->name }} — performance and genealogy reports (last 30 days)</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('operator.genealogy') }}" class="btn btn-outline-secondary btn-sm">Genealogy tree</a>
        <a href="{{ route('operator.dashboard') }}" class="btn btn-outline-primary btn-sm">Dashboard</a>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100"><div class="card-body">
            <div class="text-muted small">Your orders (30 days)</div>
            <div class="fs-3 fw-bold">{{ number_format($s['orders_last_30_days']) }}</div>
        </div></div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100"><div class="card-body">
            <div class="text-muted small">Your order value (30 days)</div>
            <div class="fs-3 fw-bold">₱{{ number_format($s['order_value_last_30_days'], 2) }}</div>
        </div></div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100"><div class="card-body">
            <div class="text-muted small">Downline operators (all levels)</div>
            <div class="fs-3 fw-bold">{{ number_format($s['downline_operators']) }}</div>
        </div></div>
    </div>
</div>

<ul class="nav nav-tabs mb-3" id="genealogyReportTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="tab-l14" data-bs-toggle="tab" data-bs-target="#panel-l14" type="button" role="tab">
            Downline Report (L1–L4)
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="tab-l04" data-bs-toggle="tab" data-bs-target="#panel-l04" type="button" role="tab">
            Full Network Report (L0–L4)
        </button>
    </li>
</ul>

<div class="tab-content" id="genealogyReportPanels">
    {{-- SECTION A: Level 1–4 Downline --}}
    <div class="tab-pane fade show active" id="panel-l14" role="tabpanel">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white fw-semibold">Level 1–4 Downline Report</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-striped mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Level</th>
                                <th class="text-end">Operators</th>
                                <th class="text-end">Orders</th>
                                <th class="text-end">Order Value</th>
                                <th class="text-end">Softserve Pts</th>
                                <th class="text-end d-none d-md-table-cell">Kilos</th>
                                <th class="text-end d-none d-lg-table-cell">Non-SS Orders</th>
                                @if ($rebates)<th class="text-end">Earnings</th>@endif
                            </tr>
                        </thead>
                        <tbody>
                        @foreach ($l14['rows'] as $row)
                            <tr>
                                <td>{{ $row['label'] }}</td>
                                <td class="text-end">{{ number_format($row['operators']) }}</td>
                                <td class="text-end">{{ number_format($row['orders']) }}</td>
                                <td class="text-end">₱{{ number_format($row['order_value'], 2) }}</td>
                                <td class="text-end">{{ number_format($row['softserve_points']) }}</td>
                                <td class="text-end d-none d-md-table-cell">{{ number_format($row['softserve_kilos'], 2) }}</td>
                                <td class="text-end d-none d-lg-table-cell">{{ number_format($row['non_softserve_orders']) }}</td>
                                @if ($rebates)<td class="text-end">₱{{ number_format($row['earnings'] ?? 0, 2) }}</td>@endif
                            </tr>
                        @endforeach
                        </tbody>
                        <tfoot class="table-group-divider fw-semibold">
                            <tr>
                                <td>{{ $l14['grand_total']['label'] }}</td>
                                <td class="text-end">{{ number_format($l14['grand_total']['operators']) }}</td>
                                <td class="text-end">{{ number_format($l14['grand_total']['orders']) }}</td>
                                <td class="text-end">₱{{ number_format($l14['grand_total']['order_value'], 2) }}</td>
                                <td class="text-end">{{ number_format($l14['grand_total']['softserve_points']) }}</td>
                                <td class="text-end d-none d-md-table-cell">{{ number_format($l14['grand_total']['softserve_kilos'], 2) }}</td>
                                <td class="text-end d-none d-lg-table-cell">{{ number_format($l14['grand_total']['non_softserve_orders']) }}</td>
                                @if ($rebates)<td class="text-end">₱{{ number_format($l14['grand_total']['earnings'] ?? 0, 2) }}</td>@endif
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            @unless ($rebates)
                <div class="card-footer bg-white small text-muted">Rebate earnings not shown (rebate system applies to operators only when orders are approved).</div>
            @endunless
        </div>

        <div class="row g-4">
            <div class="col-md-6">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-white fw-semibold small">Downline order distribution</div>
                    <div class="card-body"><canvas id="chart-downline-orders" height="200"></canvas></div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-white fw-semibold small">Downline softserve points</div>
                    <div class="card-body"><canvas id="chart-downline-points" height="200"></canvas></div>
                </div>
            </div>
        </div>
    </div>

    {{-- SECTION B: Level 0–4 Full Network --}}
    <div class="tab-pane fade" id="panel-l04" role="tabpanel">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white fw-semibold">Level 0–4 Full Network Report</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-striped mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Level</th>
                                <th class="text-end">Operators</th>
                                <th class="text-end">Orders</th>
                                <th class="text-end">Order Value</th>
                                <th class="text-end">Softserve Pts</th>
                                <th class="text-end d-none d-md-table-cell">Kilos</th>
                                <th class="text-end d-none d-lg-table-cell">Non-SS Orders</th>
                                @if ($rebates)<th class="text-end">Earnings</th>@endif
                            </tr>
                        </thead>
                        <tbody>
                        @foreach ($l04['rows'] as $row)
                            <tr class="{{ $row['level'] === 0 ? 'table-primary' : '' }}">
                                <td>{{ $row['label'] }}</td>
                                <td class="text-end">{{ number_format($row['operators']) }}</td>
                                <td class="text-end">{{ number_format($row['orders']) }}</td>
                                <td class="text-end">₱{{ number_format($row['order_value'], 2) }}</td>
                                <td class="text-end">{{ number_format($row['softserve_points']) }}</td>
                                <td class="text-end d-none d-md-table-cell">{{ number_format($row['softserve_kilos'], 2) }}</td>
                                <td class="text-end d-none d-lg-table-cell">{{ number_format($row['non_softserve_orders']) }}</td>
                                @if ($rebates)<td class="text-end">₱{{ number_format($row['earnings'] ?? 0, 2) }}</td>@endif
                            </tr>
                        @endforeach
                        </tbody>
                        <tfoot class="table-group-divider fw-semibold">
                            <tr>
                                <td>{{ $l04['grand_total']['label'] }}</td>
                                <td class="text-end">{{ number_format($l04['grand_total']['operators']) }}</td>
                                <td class="text-end">{{ number_format($l04['grand_total']['orders']) }}</td>
                                <td class="text-end">₱{{ number_format($l04['grand_total']['order_value'], 2) }}</td>
                                <td class="text-end">{{ number_format($l04['grand_total']['softserve_points']) }}</td>
                                <td class="text-end d-none d-md-table-cell">{{ number_format($l04['grand_total']['softserve_kilos'], 2) }}</td>
                                <td class="text-end d-none d-lg-table-cell">{{ number_format($l04['grand_total']['non_softserve_orders']) }}</td>
                                @if ($rebates)<td class="text-end">₱{{ number_format($l04['grand_total']['earnings'] ?? 0, 2) }}</td>@endif
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-md-4">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-white fw-semibold small">Self vs downline (orders)</div>
                    <div class="card-body"><canvas id="chart-self-vs-downline" height="200"></canvas></div>
                </div>
            </div>
            <div class="col-md-8">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-white fw-semibold small">Level contribution (order value)</div>
                    <div class="card-body"><canvas id="chart-level-value" height="200"></canvas></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
const chartData = @json($charts);

function barChart(id, labels, values, label, color) {
    const el = document.getElementById(id);
    if (!el) return;
    new Chart(el, {
        type: 'bar',
        data: {
            labels,
            datasets: [{ label, data: values, backgroundColor: color || 'rgba(13, 110, 253, 0.65)' }],
        },
        options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } },
    });
}

function pieChart(id, labels, values) {
    const el = document.getElementById(id);
    if (!el) return;
    new Chart(el, {
        type: 'pie',
        data: {
            labels,
            datasets: [{ data: values, backgroundColor: ['#0d6efd', '#198754'] }],
        },
        options: { responsive: true },
    });
}

barChart('chart-downline-orders', chartData.downlineOrders.labels, chartData.downlineOrders.orders, 'Orders');
barChart('chart-downline-points', chartData.downlineOrders.labels, chartData.downlineOrders.points, 'Points', 'rgba(25, 135, 84, 0.65)');
pieChart('chart-self-vs-downline', chartData.selfVsDownline.labels, chartData.selfVsDownline.orders);
barChart('chart-level-value', chartData.fullNetworkOrders.labels, chartData.fullNetworkOrders.values, 'Value', 'rgba(102, 16, 242, 0.65)');
</script>
@endsection
