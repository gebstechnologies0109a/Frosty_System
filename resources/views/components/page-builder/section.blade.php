@props(['title' => '', 'body' => ''])
<section class="mb-4 p-3 bg-light rounded">
    @if ($title)<h2 class="h5 fw-semibold">{{ $title }}</h2>@endif
    @if ($body)<p class="mb-0 text-muted">{{ $body }}</p>@endif
</section>
