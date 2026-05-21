@props(['user', 'size' => 40])
@php
    $url = $user->profilePhotoUrl();
    $px = (int) $size;
@endphp
@if ($url)
    <img src="{{ $url }}" alt="" class="rounded-circle" width="{{ $px }}" height="{{ $px }}" style="object-fit:cover">
@else
    <span class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center fw-semibold"
        style="width:{{ $px }}px;height:{{ $px }}px;font-size:{{ max(11, (int) ($px * 0.38)) }}px">
        {{ $user->initials() }}
    </span>
@endif
