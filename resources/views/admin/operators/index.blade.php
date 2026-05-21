@extends('layouts.app')
@section('title', 'Operators')
@section('content')
@include('admin.partials.page-header', [
    'title' => 'Operators',
    'subtitle' => 'Manage operator accounts',
    'actions' => '<a href="'.route('admin.operators.create').'" class="btn btn-primary">Add Operator</a>',
])
<form method="get" action="{{ route('admin.operators.index') }}" class="card border-0 shadow-sm mb-3">
    <div class="card-body py-3">
        <div class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label small mb-1">Search</label>
                <input type="search" name="q" class="form-control form-control-sm" value="{{ request('q') }}" placeholder="Name or email…">
            </div>
    <div class="col-md-3">
        <label class="form-label small mb-1">Status</label>
        <select name="status" class="form-select form-select-sm">
            <option value="">All</option>
            @foreach ($statuses as $s)
                <option value="{{ $s->value }}" @selected(request('status') === $s->value)>{{ ucfirst($s->value) }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3">
        <label class="form-label small mb-1">Distributor</label>
        <select name="distributor_id" class="form-select form-select-sm">
            <option value="">All</option>
            @foreach ($distributors as $d)
                <option value="{{ $d->id }}" @selected((string) request('distributor_id') === (string) $d->id)>{{ $d->name }}</option>
            @endforeach
        </select>
    </div>
            <div class="col-auto d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                <a href="{{ route('admin.operators.index') }}" class="btn btn-outline-secondary btn-sm">Reset</a>
            </div>
        </div>
    </div>
</form>
<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr><th>Name</th><th>Email</th><th>Distributor</th><th>Status</th><th class="text-end">Actions</th></tr>
            </thead>
            <tbody>
            @forelse ($operators as $op)
                <tr>
                    <td class="fw-medium">{{ $op->name }}</td>
                    <td>{{ $op->email }}</td>
                    <td>{{ $op->assignedDistributor?->name ?? '—' }}</td>
                    <td><span class="badge text-bg-{{ $op->status === \App\Enums\UserStatus::Active ? 'success' : 'secondary' }}">{{ $op->status->value }}</span></td>
                    <td>
                        @include('admin.partials.resource-actions', [
                            'view' => route('admin.operators.show', $op),
                            'edit' => route('admin.operators.edit', $op),
                            'delete' => route('admin.operators.destroy', $op),
                            'extra' => '<form method="post" action="'.route('admin.operators.toggle-status', $op).'" class="d-inline">'.csrf_field().method_field('PATCH').'<button class="btn btn-sm btn-outline-warning">Toggle</button></form>',
                        ])
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-center text-muted py-4">No operators found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">@include('partials.list-pagination', ['paginator' => $operators])</div>
@endsection
