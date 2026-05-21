@extends('layouts.app')
@section('title', $page->title)
@section('content')
<div class="mb-3">
    <h1 class="h3 mb-0">{{ $page->title }}</h1>
    <p class="text-muted small mb-0">/p/{{ $page->slug }}</p>
</div>
{!! $html !!}
@endsection
