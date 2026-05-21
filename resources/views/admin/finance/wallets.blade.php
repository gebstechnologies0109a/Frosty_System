@extends('layouts.app')
@section('title', 'Wallets')
@section('content')
<h1 class="h4 mb-3">Wallets</h1>
@include('admin.finance.nav')
<table class="table table-sm bg-white shadow-sm">
    <thead><tr><th>User</th><th>Role</th><th class="text-end">Balance</th></tr></thead>
    <tbody>
    @foreach ($wallets as $w)
        <tr><td>{{ $w->user->name }}</td><td>{{ $w->user->role->label() }}</td><td class="text-end">₱{{ number_format($w->balance, 2) }}</td></tr>
    @endforeach
    </tbody>
</table>
@include('partials.list-pagination', ['paginator' => $wallets])
@endsection
