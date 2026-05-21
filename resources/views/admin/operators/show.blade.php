@extends('layouts.app')
@section('title', $operator->name)
@section('content')
@include('admin.partials.page-header', [
    'title' => $operator->name,
    'subtitle' => $operator->email,
    'actions' => '<a href="'.route('admin.operators.edit', $operator).'" class="btn btn-outline-secondary">Edit</a>',
])
<div class="row g-3 mb-4">
    <div class="col-md-4"><div class="card p-3"><div class="text-muted small">Status</div><div class="fw-bold">{{ $operator->status->value }}</div></div></div>
    <div class="col-md-4"><div class="card p-3"><div class="text-muted small">Distributor</div><div class="fw-bold">{{ $operator->assignedDistributor?->name ?? '—' }}</div></div></div>
    <div class="col-md-4"><div class="card p-3"><div class="text-muted small">Wallet</div><div class="fw-bold">₱{{ number_format($operator->wallet?->balance ?? 0, 2) }}</div></div></div>
</div>
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header fw-semibold">Related data</div>
    <div class="list-group list-group-flush">
        <a href="{{ route('admin.operators.inventory', $operator) }}" class="list-group-item list-group-item-action">Inventory</a>
        <a href="{{ route('admin.operators.store-menu', $operator) }}" class="list-group-item list-group-item-action">Store menu</a>
        <a href="{{ route('admin.operators.pos-logs', $operator) }}" class="list-group-item list-group-item-action">POS logs ({{ $posCount }})</a>
        <a href="{{ route('admin.operators.daily-closings', $operator) }}" class="list-group-item list-group-item-action">Daily closings</a>
    </div>
</div>
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header fw-semibold">Reset password</div>
    <div class="card-body">
        <form method="post" action="{{ route('admin.operators.reset-password', $operator) }}" class="row g-2 align-items-end">
            @csrf
            <div class="col-md-4"><input type="password" name="password" class="form-control" placeholder="New password" required></div>
            <div class="col-md-4"><input type="password" name="password_confirmation" class="form-control" placeholder="Confirm" required></div>
            <div class="col-auto"><button type="submit" class="btn btn-warning">Reset password</button></div>
        </form>
    </div>
</div>
<form method="post" action="{{ route('admin.operators.toggle-status', $operator) }}" class="d-inline">@csrf @method('PATCH')<button class="btn btn-outline-warning">Toggle active/inactive</button></form>
<a href="{{ route('admin.operators.index') }}" class="btn btn-link">Back to list</a>
@endsection
