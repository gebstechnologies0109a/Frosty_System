@props(['title', 'subtitle' => null])
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
        <h1 class="h4 mb-0">{{ $title }}</h1>
        @if ($subtitle)<p class="text-muted small mb-0">{{ $subtitle }}</p>@endif
    </div>
    @if (! empty($actions))<div class="d-flex gap-2 flex-wrap">{!! $actions !!}</div>@endif
</div>
