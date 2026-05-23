@props([
    'chart_type' => 'line',
    'title' => 'Chart',
    'labels' => 'A,B,C',
    'dataset_source' => 'daily_purchases',
])
@php
    $labelList = array_map('trim', explode(',', (string) $labels));
    $canvasId = 'chart-'.substr(md5($title.$dataset_source), 0, 8);
@endphp
<div class="card border-0 shadow-sm mb-3 page-builder-chart-block">
    <div class="card-header bg-white fw-semibold">{{ $title }}</div>
    <div class="card-body">
        <canvas id="{{ $canvasId }}" height="120" data-chart-type="{{ $chart_type }}" data-labels="{{ json_encode($labelList) }}" data-source="{{ $dataset_source }}"></canvas>
        <p class="small text-muted mb-0 mt-2">Source: {{ str_replace('_', ' ', $dataset_source) }}</p>
    </div>
</div>
