@extends('layouts.app')
@section('title', 'Withdrawals')
@section('content')
<h1 class="h4 mb-3">Withdrawals</h1>
@include('admin.finance.nav')
<table class="table table-sm bg-white shadow-sm">
    <thead><tr><th>User</th><th class="text-end">Amount</th><th>Status</th><th></th></tr></thead>
    <tbody>
    @foreach ($withdrawals as $w)
        <tr>
            <td>{{ $w->user->name }}</td>
            <td class="text-end">₱{{ number_format($w->amount, 2) }}</td>
            <td>{{ $w->status->value }}</td>
            <td>
                @if ($w->status === \App\Enums\WithdrawalStatus::Pending)
                    <form class="d-inline" method="post" action="{{ route('admin.finance.withdrawals.approve', $w) }}">@csrf<button class="btn btn-sm btn-success">Approve</button></form>
                    <form class="d-inline" method="post" action="{{ route('admin.finance.withdrawals.reject', $w) }}">@csrf<button class="btn btn-sm btn-outline-danger">Reject</button></form>
                @endif
            </td>
        </tr>
    @endforeach
    </tbody>
</table>
{{ $withdrawals->links() }}
@endsection
