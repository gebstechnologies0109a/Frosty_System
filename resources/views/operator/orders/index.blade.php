@extends('layouts.app')
@section('title', 'Order History')
@section('content')
<h1 class="h4 mb-3">Order history</h1>
<a href="{{ route('operator.orders.create') }}" class="btn btn-primary btn-sm mb-3">New order</a>
<table class="table table-sm bg-white shadow-sm">
    <thead><tr><th>#</th><th>Route</th><th class="text-end">Pts</th><th>Status</th></tr></thead>
    <tbody>
    @foreach ($orders as $o)
        <tr>
            <td>{{ $o->id }}</td>
            <td>{{ $o->distributor ? $o->distributor->name : 'Main' }}</td>
            <td class="text-end">{{ $o->total_points }}</td>
            <td>{{ $o->status->value }}</td>
        </tr>
    @endforeach
    </tbody>
</table>
{{ $orders->links() }}
@endsection
