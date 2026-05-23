@extends('layouts.app')
@section('title', 'Preview: '.$page->title)
@section('content')
@include('admin.partials.page-header', [
    'title' => 'Preview: '.$page->title,
    'subtitle' => '/p/'.$page->slug,
    'actions' => '<a href="'.route('admin.page-builder.index').'" class="btn btn-outline-secondary">Page Builder</a>
        <a href="'.route('admin.page-builder.edit', $page).'" class="btn btn-outline-secondary">Classic edit</a>
        <a href="'.route('pages.show', $page->slug).'" class="btn btn-primary" target="_blank">Open live</a>',
])
<div class="card border-0 shadow-sm">
    <div class="card-body">{!! $html !!}</div>
</div>
@endsection
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (window.pageBuilderChartsInit) return;
    window.pageBuilderChartsInit = true;
    document.querySelectorAll('.page-builder-chart-block canvas').forEach((canvas) => {
        const labels = JSON.parse(canvas.dataset.labels || '[]');
        const type = canvas.dataset.chartType === 'bar' ? 'bar' : 'line';
        new Chart(canvas, {
            type,
            data: {
                labels: labels.length ? labels : ['A', 'B', 'C'],
                datasets: [{ label: 'Sample', data: labels.map(() => Math.floor(Math.random() * 50) + 5), borderColor: '#0d6efd', backgroundColor: 'rgba(13,110,253,.2)' }],
            },
            options: { responsive: true, maintainAspectRatio: true, plugins: { legend: { display: false } } },
        });
    });
});
</script>
@endpush
