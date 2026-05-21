@extends('layouts.app')
@section('title', 'Operator Dashboard')
@section('content')
<h1 class="h3 mb-4">Operator Dashboard</h1>
<div class="row g-3 mb-4">
    <div class="col-md-4"><div class="card p-3"><div class="text-muted small">Wallet</div><div class="fs-4 fw-bold">₱{{ number_format($wallet->balance, 2) }}</div></div></div>
    <div class="col-md-4"><div class="card p-3"><div class="text-muted small">Personal points ({{ \App\Support\FrostySettings::currentMonth() }})</div><div class="fs-4 fw-bold">{{ $qualification->personal_points ?? 0 }} / {{ $threshold }}</div></div>
    <div class="col-md-4"><div class="card p-3"><div class="text-muted small">Override status</div><div class="fs-5 fw-bold">@if($qualification?->qualified)<span class="text-success">Qualified</span>@else<span class="text-secondary">Not eligible</span>@endif</div></div></div>
</div>
<div class="d-flex gap-2 mb-4 flex-wrap">
    <a href="{{ route('operator.pos.index') }}" class="btn btn-success fw-semibold">Frosty POS</a>
    <a href="{{ route('operator.orders.create') }}" class="btn btn-primary">New order</a>
    <a href="{{ route('operator.referrals.create') }}" class="btn btn-outline-primary">Add operator</a>
    <a href="{{ route('operator.rebates') }}" class="btn btn-outline-secondary">Rebates</a>
</div>
<h2 class="h6">Recent orders</h2>
<ul class="list-group">@forelse($recentOrders as $o)<li class="list-group-item">#{{ $o->id }} — {{ $o->total_points }} pts — {{ $o->status->value }}</li>@empty<li class="list-group-item text-muted">None</li>@endforelse</ul>
@endsection
