@extends('layouts.operator')
@section('header_title', 'Rebates')
@section('title', 'Rebates')
@section('content')
<h1 class="h4 mb-3">Rebate history</h1>
<table class="table table-sm bg-white shadow-sm">
    <thead><tr><th>Type</th><th>Level</th><th>From</th><th class="text-end">Pts</th><th class="text-end">₱</th><th>Month</th></tr></thead>
    <tbody>
    @foreach ($entries as $e)
        <tr>
            <td>{{ $e->type->value }}</td>
            <td>{{ $e->level }}</td>
            <td>{{ $e->sourceUser?->name ?? '—' }}</td>
            <td class="text-end">{{ $e->points }}</td>
            <td class="text-end">₱{{ number_format($e->pesos, 2) }}</td>
            <td>{{ $e->month }}</td>
        </tr>
    @endforeach
    </tbody>
</table>
@include('partials.list-pagination', ['paginator' => $entries])
@endsection
