@extends('layouts.app')
@section('title', 'Finance Reports')
@section('content')
<h1 class="h4 mb-3">Reports — {{ $month }}</h1>
@include('admin.finance.nav')
<div class="row g-3">
    <div class="col-md-4"><div class="card p-3"><div class="text-muted small">Self rebates</div><div class="fs-4">₱{{ number_format($selfTotal, 2) }}</div></div></div>
    <div class="col-md-4"><div class="card p-3"><div class="text-muted small">Override rebates</div><div class="fs-4">₱{{ number_format($overrideTotal, 2) }}</div></div></div>
    <div class="col-md-4"><div class="card p-3"><div class="text-muted small">Pending withdrawals</div><div class="fs-4">{{ $pendingWithdrawals }}</div></div></div>
</div>
<h2 class="h6 mt-4">Overrides by level</h2>
<ul class="list-group col-md-4">
    @foreach ($byLevel as $level => $total)
        <li class="list-group-item d-flex justify-content-between">Level {{ $level }}<span>₱{{ number_format($total, 2) }}</span></li>
    @endforeach
</ul>
@endsection
