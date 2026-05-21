@extends('layouts.app')
@section('title', $distributor->name)
@section('content')
@include('admin.partials.page-header', [
    'title' => $distributor->name,
    'actions' => '<a href="'.route('admin.distributors.edit', $distributor).'" class="btn btn-outline-secondary">Edit</a><a href="'.route('admin.distributors.orders', $distributor).'" class="btn btn-outline-primary">Orders</a>',
])
<div class="row g-3 mb-3">
    <div class="col-md-4"><div class="card p-3"><div class="small text-muted">Status</div><div class="fw-bold">{{ $distributor->status->value }}</div></div></div>
    <div class="col-md-4"><div class="card p-3"><div class="small text-muted">Referred operators</div><div class="fw-bold">{{ $operatorCount }}</div></div></div>
    <div class="col-md-4"><div class="card p-3"><div class="small text-muted">Orders</div><div class="fw-bold">{{ $orderCount }}</div></div></div>
</div>
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header fw-semibold">Reset password</div>
    <div class="card-body">
        <form method="post" action="{{ route('admin.distributors.reset-password', $distributor) }}" class="row g-2">@csrf
            <div class="col-md-4"><input type="password" name="password" class="form-control" required placeholder="New password"></div>
            <div class="col-md-4"><input type="password" name="password_confirmation" class="form-control" required placeholder="Confirm"></div>
            <div class="col-auto"><button class="btn btn-warning">Reset</button></div>
        </form>
    </div>
</div>
<a href="{{ route('admin.distributors.index') }}" class="btn btn-link">Back</a>
@endsection
