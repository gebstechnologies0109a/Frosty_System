@extends('layouts.app')
@section('title', 'Rebates')
@section('content')
<h1 class="h4 mb-3">Points & Rebates Ledger</h1>
@include('admin.finance.nav')
<table class="table table-sm bg-white shadow-sm">
    <thead><tr><th>User</th><th>Type</th><th>Lvl</th><th class="text-end">Pts</th><th class="text-end">₱</th><th>Month</th></tr></thead>
    <tbody>
    @foreach ($entries as $e)
        <tr>
            <td>{{ $e->user->name }}</td>
            <td>{{ $e->type->value }}</td>
            <td>{{ $e->level }}</td>
            <td class="text-end">{{ $e->points }}</td>
            <td class="text-end">₱{{ number_format($e->pesos, 2) }}</td>
            <td>{{ $e->month }}</td>
        </tr>
    @endforeach
    </tbody>
</table>
{{ $entries->links() }}
@endsection
