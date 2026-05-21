@extends('layouts.app')
@section('title', 'Admin Dashboard')
@section('content')
<h1 class="h3 mb-4">Admin Dashboard</h1>
<div class="row g-3">
    @foreach ($stats as $label => $value)
        <div class="col-md-4"><div class="card p-3"><div class="text-muted small text-capitalize">{{ str_replace('_', ' ', $label) }}</div><div class="fs-3 fw-bold">{{ $value }}</div></div></div>
    @endforeach
</div>
@endsection
