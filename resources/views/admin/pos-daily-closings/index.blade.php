@extends('layouts.app')
@section('title', 'POS Daily Closings')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h1 class="h3 mb-1">POS Daily Closings</h1>
        <p class="text-muted small mb-0">Audit operator end-of-day reports and variances</p>
    </div>
    <a href="{{ route('admin.pos-sales-logs.secure') }}" class="btn btn-outline-primary btn-sm">Full POS transaction logs</a>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <form method="get" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small">Operator</label>
                <select name="operator_id" class="form-select form-select-sm">
                    <option value="">All</option>
                    @foreach ($operators as $op)
                        <option value="{{ $op->id }}" @selected(($filters['operator_id'] ?? '') == $op->id)>{{ $op->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">All</option>
                    <option value="pending" @selected(($filters['status'] ?? '') === 'pending')>Pending</option>
                    <option value="approved" @selected(($filters['status'] ?? '') === 'approved')>Approved</option>
                    <option value="rejected" @selected(($filters['status'] ?? '') === 'rejected')>Rejected</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small">From</label>
                <input type="date" name="date_from" class="form-control form-control-sm" value="{{ $filters['date_from'] ?? '' }}">
            </div>
            <div class="col-md-2">
                <label class="form-label small">To</label>
                <input type="date" name="date_to" class="form-control form-control-sm" value="{{ $filters['date_to'] ?? '' }}">
            </div>
            <div class="col-auto"><button type="submit" class="btn btn-primary btn-sm">Filter</button></div>
        </form>
    </div>
</div>

<div class="table-responsive card shadow-sm">
    <table class="table table-sm table-hover mb-0">
        <thead class="table-light">
            <tr>
                <th>Date</th>
                <th>Operator</th>
                <th class="text-end">Sales</th>
                <th class="text-end">COGS</th>
                <th class="text-end">Profit</th>
                <th class="text-end">Margin</th>
                <th class="text-end">Variance</th>
                <th>Status</th>
                <th class="text-end">Actions</th>
            </tr>
        </thead>
        <tbody>
        @forelse ($closings as $c)
            <tr>
                <td>{{ $c->closing_date->format('M j, Y') }}</td>
                <td>{{ $c->operator?->name }}</td>
                <td class="text-end">₱{{ number_format($c->total_sales, 2) }}</td>
                <td class="text-end">₱{{ number_format($c->total_cogs, 2) }}</td>
                <td class="text-end">₱{{ number_format($c->gross_profit, 2) }}</td>
                <td class="text-end">{{ number_format($c->gross_margin_percent, 1) }}%</td>
                <td class="text-end {{ $c->variance < 0 ? 'text-danger' : ($c->variance > 0 ? 'text-success' : '') }}">
                    ₱{{ number_format($c->variance, 2) }}
                </td>
                <td>
                    <span class="badge text-bg-{{ $c->status->value === 'approved' ? 'success' : ($c->status->value === 'rejected' ? 'danger' : 'warning') }}">
                        {{ $c->status->label() }}
                    </span>
                </td>
                <td class="text-end text-nowrap">
                    <a href="{{ route('admin.pos-sales-logs.index', ['operator_id' => $c->operator_id, 'date_from' => $c->closing_date->format('Y-m-d'), 'date_to' => $c->closing_date->format('Y-m-d')]) }}" class="btn btn-sm btn-outline-secondary">Logs</a>
                    @if ($c->status->value === 'pending')
                        <form method="post" action="{{ route('admin.pos.daily-closing.approve', $c) }}" class="d-inline">@csrf
                            <button type="submit" class="btn btn-sm btn-success">Approve</button>
                        </form>
                        <form method="post" action="{{ route('admin.pos.daily-closing.reject', $c) }}" class="d-inline">@csrf
                            <button type="submit" class="btn btn-sm btn-outline-danger">Reject</button>
                        </form>
                    @endif
                    <form method="post" action="{{ route('admin.pos.daily-closing.reopen', $c) }}" class="d-inline" onsubmit="return confirm('Remove this closing and unlock the day for the operator?')">@csrf
                        <button type="submit" class="btn btn-sm btn-outline-warning">Reopen</button>
                    </form>
                </td>
            </tr>
            @if ($c->notes)
                <tr><td colspan="9" class="small text-muted py-1 ps-4">Notes: {{ $c->notes }}</td></tr>
            @endif
        @empty
            <tr><td colspan="9" class="text-center text-muted py-4">No daily closings found.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>

{{ $closings->links() }}
@endsection
