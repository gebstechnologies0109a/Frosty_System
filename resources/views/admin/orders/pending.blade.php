@extends('layouts.app')
@section('title', 'Pending Orders')
@section('content')
@include('admin.partials.page-header', ['title' => 'Pending Orders', 'subtitle' => 'Approve or reject supply orders'])
<form method="get" class="card border-0 shadow-sm mb-3"><div class="card-body row g-2 align-items-end">
    <div class="col-md-4"><label class="form-label small">Search</label><input name="q" class="form-control form-control-sm" value="{{ request('q') }}"></div>
    <div class="col-md-3"><label class="form-label small">Distributor</label><select name="distributor_id" class="form-select form-select-sm"><option value="">All</option>@foreach ($distributors as $d)<option value="{{ $d->id }}" @selected((string) request('distributor_id') === (string) $d->id)>{{ $d->name }}</option>@endforeach</select></div>
    <div class="col-auto"><button class="btn btn-primary btn-sm">Filter</button> <a href="{{ route('admin.orders.pending') }}" class="btn btn-outline-secondary btn-sm">Reset</a></div>
</div></form>
<div class="card border-0 shadow-sm">
    <table class="table table-hover mb-0">
        <thead class="table-light"><tr><th>#</th><th>Placed by</th><th>Distributor</th><th class="text-end">Points</th><th class="text-end">Actions</th></tr></thead>
        <tbody>
        @forelse ($orders as $order)
            <tr>
                <td><a href="{{ route('admin.orders.show', $order) }}" class="fw-semibold text-decoration-none">#{{ $order->id }}</a></td>
                <td>{{ $order->user->name }}</td>
                <td>{{ $order->distributor->name }}</td>
                <td class="text-end">{{ $order->total_points }}</td>
                <td class="text-end">
                    <a href="{{ route('admin.orders.show', $order) }}" class="btn btn-sm btn-outline-primary">View</a>
                    <form class="d-inline" method="post" action="{{ route('admin.orders.pending.approve', $order) }}">@csrf<button class="btn btn-sm btn-success">Approve</button></form>
                    <form class="d-inline" method="post" action="{{ route('admin.orders.pending.reject', $order) }}">@csrf<button class="btn btn-sm btn-outline-danger">Reject</button></form>
                </td>
            </tr>
        @empty
            <tr><td colspan="5" class="text-center text-muted py-3">No pending orders.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
{{ $orders->links() }}
@endsection
