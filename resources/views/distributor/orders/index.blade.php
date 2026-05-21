@extends('layouts.app')
@section('title', 'Distributor Orders')
@section('content')
<div class="d-flex justify-content-between mb-3">
    <h1 class="h4">Orders</h1>
    <a href="{{ route('distributor.orders.create') }}" class="btn btn-primary">Order from Main</a>
</div>
<h2 class="h6">Operator orders routed to me</h2>
<table class="table table-sm bg-white shadow-sm mb-4">
    <thead><tr><th>#</th><th>Operator</th><th>Pts</th><th>Status</th><th></th></tr></thead>
    <tbody>
    @foreach ($operatorOrders as $o)
        <tr>
            <td>{{ $o->id }}</td><td>{{ $o->user->name }}</td><td>{{ $o->total_points }}</td><td>{{ $o->status->value }}</td>
            <td>@if($o->status === \App\Enums\OrderStatus::Pending)<form method="post" action="{{ route('distributor.orders.approve', $o) }}">@csrf<button class="btn btn-sm btn-success">Approve</button></form>@endif</td>
        </tr>
    @endforeach
    </tbody>
</table>
@include('partials.list-pagination', ['paginator' => $operatorOrders])
<h2 class="h6 mt-4">My orders to Main</h2>
<table class="table table-sm bg-white shadow-sm">
    <thead><tr><th>#</th><th>Pts</th><th>Status</th></tr></thead>
    <tbody>@foreach ($myOrders as $o)<tr><td>{{ $o->id }}</td><td>{{ $o->total_points }}</td><td>{{ $o->status->value }}</td></tr>@endforeach</tbody>
</table>
@include('partials.list-pagination', ['paginator' => $myOrders])
@endsection
