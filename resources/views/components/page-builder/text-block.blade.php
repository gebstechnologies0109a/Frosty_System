@props(['heading' => '', 'body' => ''])
<div class="mb-3">
    @if ($heading)<h3 class="h5 fw-semibold">{{ $heading }}</h3>@endif
    @if ($body)<p class="mb-0">{{ $body }}</p>@endif
</div>
