@extends('layouts.app')
@section('title', 'Daily closings')
@section('content')
@include('admin.partials.page-header', ['title' => 'Daily closings — '.$operator->name, 'actions' => '<a href="'.route('admin.pos.daily-closings.index', ['operator_id' => $operator->id]).'" class="btn btn-outline-primary btn-sm">All closings</a>'])
<div class="card border-0 shadow-sm">
    <table class="table table-sm mb-0">
        <thead class="table-light"><tr><th>Date</th><th>Status</th><th class="text-end">Expected</th><th class="text-end">Actual</th><th class="text-end">Variance</th></tr></thead>
        <tbody>
        @forelse ($closings as $c)
            <tr>
                <td>{{ $c->closing_date->format('M j, Y') }}</td>
                <td><span class="badge text-bg-secondary">{{ $c->status->label() }}</span></td>
                <td class="text-end">₱{{ number_format($c->expected_cash, 2) }}</td>
                <td class="text-end">₱{{ number_format($c->actual_cash, 2) }}</td>
                <td class="text-end">₱{{ number_format($c->variance, 2) }}</td>
            </tr>
        @empty
            <tr><td colspan="5" class="text-muted text-center py-3">No closings submitted.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
{{ $closings->links() }}
@endsection
