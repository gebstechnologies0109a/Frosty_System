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

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
        <h1 class="h4 mb-1 fw-bold">Analytics</h1>
        <p class="text-muted small mb-0">{{ $operator->name }} — last 30 days</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('operator.genealogy') }}" class="btn btn-outline-secondary btn-sm"><i class="fa-solid fa-sitemap me-1"></i>Genealogy</a>
        <a href="{{ route('operator.dashboard') }}" class="btn btn-outline-primary btn-sm"><i class="fa-solid fa-gauge-high me-1"></i>Dashboard</a>
    </div>
</div>

<div class="frosty-grid-stats mb-4">
    <div class="frosty-stat-card">
        <div class="text-muted small"><i class="fa-solid fa-cart-shopping me-1"></i>Your orders</div>
        <div class="fs-3 fw-bold">{{ number_format($s['orders_last_30_days']) }}</div>
        <div class="text-muted small">Last 30 days</div>
    </div>
    <div class="frosty-stat-card">
        <div class="text-muted small"><i class="fa-solid fa-peso-sign me-1"></i>Order value</div>
        <div class="fs-3 fw-bold text-frosty-primary">₱{{ number_format($s['order_value_last_30_days'], 2) }}</div>
        <div class="text-muted small">Last 30 days</div>
    </div>
    <div class="frosty-stat-card">
        <div class="text-muted small"><i class="fa-solid fa-users me-1"></i>Downline operators</div>
        <div class="fs-3 fw-bold">{{ number_format($s['downline_operators']) }}</div>
        <div class="text-muted small">All levels</div>
    </div>
</div>

<ul class="nav nav-pills mb-3 gap-1" id="genealogyReportTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active btn-sm" id="tab-l14" data-bs-toggle="tab" data-bs-target="#panel-l14" type="button" role="tab">
            <i class="fa-solid fa-chart-bar me-1"></i>L1–L4
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link btn-sm" id="tab-l04" data-bs-toggle="tab" data-bs-target="#panel-l04" type="button" role="tab">
            <i class="fa-solid fa-network-wired me-1"></i>L0–L4
        </button>
    </li>
</ul>

<div class="tab-content" id="genealogyReportPanels">
    <div class="tab-pane fade show active" id="panel-l14" role="tabpanel">
        <div class="card border-0 shadow-sm mb-4 frosty-chart-card">
            <div class="card-header fw-semibold">Level 1–4 Downline Report</div>
            <div class="table-responsive">
                <table class="table table-sm table-striped mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Level</th>
                            <th class="text-end">Operators</th>
                            <th class="text-end">Orders</th>
                            <th class="text-end">Value</th>
                            <th class="text-end">Pts</th>
                            @if ($rebates)<th class="text-end">Earn</th>@endif
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
                            @if ($rebates)<td class="text-end">₱{{ number_format($row['earnings'] ?? 0, 2) }}</td>@endif
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="frosty-grid-analytics mb-4">
            <div class="frosty-chart-card">
                <div class="card-header">Downline orders by level</div>
                <div class="card-body" style="height:220px"><canvas id="chart-downline-orders"></canvas></div>
            </div>
            <div class="frosty-chart-card">
                <div class="card-header">Downline softserve points</div>
                <div class="card-body" style="height:220px"><canvas id="chart-downline-points"></canvas></div>
            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="panel-l04" role="tabpanel">
        <div class="card border-0 shadow-sm mb-4 frosty-chart-card">
            <div class="card-header fw-semibold">Level 0–4 Full Network Report</div>
            <div class="table-responsive">
                <table class="table table-sm table-striped mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Level</th>
                            <th class="text-end">Operators</th>
                            <th class="text-end">Orders</th>
                            <th class="text-end">Value</th>
                            @if ($rebates)<th class="text-end">Earn</th>@endif
                        </tr>
                    </thead>
                    <tbody>
                    @foreach ($l04['rows'] as $row)
                        <tr class="{{ $row['level'] === 0 ? 'table-primary' : '' }}">
                            <td>{{ $row['label'] }}</td>
                            <td class="text-end">{{ number_format($row['operators']) }}</td>
                            <td class="text-end">{{ number_format($row['orders']) }}</td>
                            <td class="text-end">₱{{ number_format($row['order_value'], 2) }}</td>
                            @if ($rebates)<td class="text-end">₱{{ number_format($row['earnings'] ?? 0, 2) }}</td>@endif
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="frosty-grid-analytics cols-3">
            <div class="frosty-chart-card">
                <div class="card-header">Self vs downline</div>
                <div class="card-body" style="height:220px"><canvas id="chart-self-vs-downline"></canvas></div>
            </div>
            <div class="frosty-chart-card" style="grid-column: span 1">
                <div class="card-header">Level order value</div>
                <div class="card-body" style="height:220px"><canvas id="chart-level-value"></canvas></div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>window.FrostyAnalyticsCharts = @json($charts);</script>
@vite('resources/js/operator-analytics-charts.js')
@endpush
