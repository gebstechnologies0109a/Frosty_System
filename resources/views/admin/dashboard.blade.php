@extends('layouts.app')
@section('title', 'Admin Dashboard')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Admin Dashboard</h1>
    @if (auth()->user()->role === \App\Enums\UserRole::SuperAdmin)
        <a href="{{ route('admin.users.create') }}" class="btn btn-primary">Add User</a>
    @endif
</div>
<div class="row g-3">
    @foreach ($stats as $label => $value)
        <div class="col-md-4"><div class="card p-3"><div class="text-muted small text-capitalize">{{ str_replace('_', ' ', $label) }}</div><div class="fs-3 fw-bold">{{ $value }}</div></div></div>
    @endforeach
</div>
@endsection
