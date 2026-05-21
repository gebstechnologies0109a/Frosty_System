@extends('layouts.app')
@section('title', 'Distributor orders')
@section('content')
@include('admin.partials.page-header', ['title' => 'Orders — '.$distributor->name])
<div class="card border-0 shadow-sm">
    <table class="table table-sm mb-0">
        <thead class="table-light"><tr><th>#</th><th>User</th><th>Status</th><th class="text-end">Points</th><th>Date</th></tr></thead>
        <tbody>
        @forelse ($orders as $o)
            <tr>
                <td>{{ $o->id }}</td>
                <td>{{ $o->user->name }}</td>
                <td>{{ $o->status->value }}</td>
                <td class="text-end">{{ $o->total_points }}</td>
                <td>{{ $o->created_at->format('M j, Y') }}</td>
            </tr>
        @empty
            <tr><td colspan="5" class="text-center text-muted py-3">No orders.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@include('partials.list-pagination', ['paginator' => $orders])
@endsection
