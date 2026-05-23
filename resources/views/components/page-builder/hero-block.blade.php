@props([
    'heading' => '',
    'subheading' => '',
    'button_label' => '',
    'button_link' => '#',
])
<div class="p-4 p-md-5 mb-4 rounded-3 text-white page-builder-hero" style="background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);">
    @if ($heading)<h1 class="display-6 fw-bold">{{ $heading }}</h1>@endif
    @if ($subheading)<p class="lead mb-3 opacity-90">{{ $subheading }}</p>@endif
    @if ($button_label)
        <a href="{{ $button_link }}" class="btn btn-light btn-lg">{{ $button_label }}</a>
    @endif
</div>
