@php
    /** @var list<array<string, mixed>> $blocks */
    $blocks = $blocks ?? [];
@endphp
<div class="page-builder-rendered">
@foreach ($blocks as $block)
    @php
        $type = (string) ($block['type'] ?? '');
        $props = is_array($block['props'] ?? null) ? $block['props'] : $block;
        unset($props['id'], $props['type']);
    @endphp
    @switch($type)
        @case('metric_card')
            @include('components.page-builder.metric-card', $props)
            @break
        @case('chart_block')
            @include('components.page-builder.chart-block', $props)
            @break
        @case('system_health_panel')
            @include('components.page-builder.system-health-panel', $props)
            @break
        @case('activity_feed')
            @include('components.page-builder.activity-feed', $props)
            @break
        @case('section')
            @include('components.page-builder.section', $props)
            @break
        @case('text_block')
            @include('components.page-builder.text-block', $props)
            @break
        @case('hero_block')
            @include('components.page-builder.hero-block', $props)
            @break
        @case('divider')
            @include('components.page-builder.divider', $props)
            @break
        @case('spacer')
            @include('components.page-builder.spacer', $props)
            @break
        @default
            <div class="alert alert-secondary small mb-3">Unknown block type: {{ $type ?: '—' }}</div>
    @endswitch
@endforeach
</div>
