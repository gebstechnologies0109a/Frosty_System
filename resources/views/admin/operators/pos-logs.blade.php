@extends('layouts.app')
@section('title', 'POS logs')
@section('content')
@include('admin.partials.page-header', ['title' => 'POS logs — '.$operator->name])
<div class="card border-0 shadow-sm">
    <table class="table table-sm mb-0">
        <thead class="table-light"><tr><th>#</th><th>Date</th><th class="text-end">Total</th><th class="text-end">Profit</th></tr></thead>
        <tbody>
        @forelse ($orders as $o)
            <tr>
                <td>{{ $o->id }}</td>
                <td>{{ $o->created_at->format('M j, Y H:i') }}</td>
                <td class="text-end">₱{{ number_format($o->total_amount, 2) }}</td>
                <td class="text-end text-success">₱{{ number_format($o->gross_profit ?? 0, 2) }}</td>
            </tr>
        @empty
            <tr><td colspan="4" class="text-muted text-center py-3">No POS sales.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@include('partials.list-pagination', ['paginator' => $orders])
@endsection
