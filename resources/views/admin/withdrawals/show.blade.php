@extends('layouts.app')
@section('title', 'Withdrawal #'.$withdrawal->id)
@section('content')
@include('admin.partials.page-header', ['title' => 'Withdrawal #'.$withdrawal->id, 'subtitle' => $withdrawal->user->name])
<div class="row g-3 mb-3">
    <div class="col-md-4"><div class="card p-3"><div class="small text-muted">Amount</div><div class="fs-4 fw-bold">₱{{ number_format($withdrawal->amount, 2) }}</div></div></div>
    <div class="col-md-4"><div class="card p-3"><div class="small text-muted">Wallet balance</div><div class="fs-4 fw-bold">₱{{ number_format($withdrawal->user->wallet?->balance ?? 0, 2) }}</div></div></div>
    <div class="col-md-4"><div class="card p-3"><div class="small text-muted">Status</div><div class="fs-5 fw-bold">{{ $withdrawal->status->value }}</div></div></div>
</div>
<div class="d-flex gap-2 mb-4">
    <form method="post" action="{{ route('admin.withdrawals.pending.approve', $withdrawal) }}">@csrf<button class="btn btn-success">Approve</button></form>
    <form method="post" action="{{ route('admin.withdrawals.pending.reject', $withdrawal) }}" class="flex-grow-1">@csrf
        <div class="input-group">
            <input type="text" name="notes" class="form-control" placeholder="Rejection notes (optional)">
            <button class="btn btn-outline-danger">Reject</button>
        </div>
    </form>
</div>
<div class="card border-0 shadow-sm">
    <div class="card-header fw-semibold">Wallet transaction log</div>
    <table class="table table-sm mb-0">
        <thead class="table-light"><tr><th>Date</th><th>Type</th><th class="text-end">Amount</th><th class="text-end">Balance after</th></tr></thead>
        <tbody>
        @forelse ($walletLogs as $log)
            <tr>
                <td>{{ $log->created_at?->format('M j, Y H:i') }}</td>
                <td>{{ $log->reference_type }}</td>
                <td class="text-end">₱{{ number_format($log->amount, 2) }}</td>
                <td class="text-end">₱{{ number_format($log->balance_after, 2) }}</td>
            </tr>
        @empty
            <tr><td colspan="4" class="text-center text-muted py-3">No wallet transactions.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<a href="{{ route('admin.withdrawals.pending') }}" class="btn btn-link mt-2">Back</a>
@endsection
