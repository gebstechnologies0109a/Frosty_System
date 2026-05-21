@extends('layouts.app')
@section('title', 'Wallet')
@section('content')
<h1 class="h4 mb-3">Wallet — ₱{{ number_format($wallet->balance, 2) }}</h1>
<form method="post" action="{{ route('operator.wallet.withdraw') }}" class="card p-3 col-md-5 mb-4">
    @csrf
    <label class="form-label">Request withdrawal (₱)</label>
    <input type="number" name="amount" step="0.01" min="100" class="form-control mb-2" required>
    <button class="btn btn-primary btn-sm">Submit</button>
</form>
<h2 class="h6">Withdrawal history</h2>
<ul class="list-group col-md-6">
    @forelse ($withdrawals as $w)
        <li class="list-group-item d-flex justify-content-between">₱{{ number_format($w->amount, 2) }} <span>{{ $w->status->value }}</span></li>
    @empty
        <li class="list-group-item text-muted">None</li>
    @endforelse
</ul>
@endsection
