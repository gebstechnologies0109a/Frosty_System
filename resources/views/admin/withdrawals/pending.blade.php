@extends('layouts.app')
@section('title', 'Pending Withdrawals')
@section('content')
@include('admin.partials.page-header', ['title' => 'Pending Withdrawals'])
<form method="get" class="card border-0 shadow-sm mb-3"><div class="card-body row g-2">
    <div class="col-md-4"><input name="q" class="form-control form-control-sm" placeholder="Search user…" value="{{ request('q') }}"></div>
    <div class="col-auto"><button class="btn btn-primary btn-sm">Search</button></div>
</div></form>
<div class="card border-0 shadow-sm">
    <table class="table table-hover mb-0">
        <thead class="table-light"><tr><th>User</th><th class="text-end">Amount</th><th>Requested</th><th class="text-end">Actions</th></tr></thead>
        <tbody>
        @forelse ($withdrawals as $w)
            <tr>
                <td>{{ $w->user->name }}<br><span class="small text-muted">{{ $w->user->email }}</span></td>
                <td class="text-end fw-bold">₱{{ number_format($w->amount, 2) }}</td>
                <td>{{ $w->created_at->format('M j, Y') }}</td>
                <td class="text-end">
                    <a href="{{ route('admin.withdrawals.pending.show', $w) }}" class="btn btn-sm btn-outline-primary">View</a>
                    <form class="d-inline" method="post" action="{{ route('admin.withdrawals.pending.approve', $w) }}">@csrf<button class="btn btn-sm btn-success">Approve</button></form>
                </td>
            </tr>
        @empty
            <tr><td colspan="4" class="text-center text-muted py-3">No pending withdrawals.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@include('partials.list-pagination', ['paginator' => $withdrawals])
@endsection
