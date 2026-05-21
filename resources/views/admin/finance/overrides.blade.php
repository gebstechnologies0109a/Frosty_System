@extends('layouts.app')
@section('title', 'Overrides')
@section('content')
<h1 class="h4 mb-3">Override rebates</h1>
@include('admin.finance.nav')
<table class="table table-sm bg-white shadow-sm">
    <thead><tr><th>Earner</th><th>Level</th><th>From</th><th class="text-end">₱</th><th>Month</th></tr></thead>
    <tbody>
    @foreach ($entries as $e)
        <tr>
            <td>{{ $e->user->name }}</td>
            <td>L{{ $e->level }}</td>
            <td>{{ $e->sourceUser?->name }}</td>
            <td class="text-end">₱{{ number_format($e->pesos, 2) }}</td>
            <td>{{ $e->month }}</td>
        </tr>
    @endforeach
    </tbody>
</table>
{{ $entries->links() }}
@endsection
