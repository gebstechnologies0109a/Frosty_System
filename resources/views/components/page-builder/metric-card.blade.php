@props([
    'title' => 'Metric',
    'value' => '0',
    'subtext' => '',
    'icon' => 'chart',
    'status' => 'default',
])
@php
    $statusClass = match ($status) {
        'success' => 'text-success',
        'warning' => 'text-warning',
        'danger' => 'text-danger',
        default => 'text-primary',
    };
    $iconClass = match ($icon) {
        'users' => 'fa-users',
        'cart' => 'fa-cart-shopping',
        'chart' => 'fa-chart-line',
        default => 'fa-gauge-high',
    };
@endphp
<div class="card border-0 shadow-sm h-100 page-builder-metric-card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-start gap-2">
            <div class="min-w-0">
                <div class="text-muted small text-truncate">{{ $title }}</div>
                <div class="fs-3 fw-bold {{ $statusClass }}">{{ $value }}</div>
                @if ($subtext)<div class="small text-muted">{{ $subtext }}</div>@endif
            </div>
            <i class="fa-solid {{ $iconClass }} fa-lg opacity-50"></i>
        </div>
    </div>
</div>
