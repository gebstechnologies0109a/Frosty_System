@extends('layouts.app')
@section('title', 'Stock logs')
@section('content')
@include('admin.partials.page-header', ['title' => 'Stock logs — '.$product->name])
<div class="card border-0 shadow-sm">
    <table class="table table-sm mb-0">
        <thead class="table-light"><tr><th>Date</th><th>Action</th><th>User</th><th class="text-end">Change</th><th class="text-end">After</th></tr></thead>
        <tbody>
        @forelse ($movements as $m)
            <tr>
                <td>{{ $m->created_at?->format('M j, Y H:i') }}</td>
                <td>{{ $m->actionLabel() }}</td>
                <td>{{ $m->user?->name ?? '—' }}</td>
                <td class="text-end">{{ $m->quantity_change >= 0 ? '+' : '' }}{{ $m->quantity_change }}</td>
                <td class="text-end">{{ $m->stock_after }}</td>
            </tr>
        @empty
            <tr><td colspan="5" class="text-center text-muted py-3">No movements.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@include('partials.list-pagination', ['paginator' => $movements])
@endsection
