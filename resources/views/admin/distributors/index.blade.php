@extends('layouts.app')
@section('title', 'Distributors')
@section('content')
@include('admin.partials.page-header', [
    'title' => 'Distributors',
    'actions' => '<a href="'.route('admin.distributors.create').'" class="btn btn-primary">Add Distributor</a>',
])
<form method="get" class="card border-0 shadow-sm mb-3">
    <div class="card-body py-3 row g-2 align-items-end">
        <div class="col-md-4"><label class="form-label small">Search</label><input type="search" name="q" class="form-control form-control-sm" value="{{ request('q') }}"></div>
        <div class="col-md-3">
            <label class="form-label small">Status</label>
            <select name="status" class="form-select form-select-sm">
                <option value="">All</option>
                @foreach ($statuses as $s)<option value="{{ $s->value }}" @selected(request('status') === $s->value)>{{ ucfirst($s->value) }}</option>@endforeach
            </select>
        </div>
        <div class="col-auto"><button class="btn btn-primary btn-sm">Filter</button> <a href="{{ route('admin.distributors.index') }}" class="btn btn-outline-secondary btn-sm">Reset</a></div>
    </div>
</form>
<div class="card border-0 shadow-sm">
    <table class="table table-hover mb-0">
        <thead class="table-light"><tr><th>Name</th><th>Email</th><th>Main</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
        <tbody>
        @forelse ($distributors as $d)
            <tr>
                <td>{{ $d->name }}</td>
                <td>{{ $d->email }}</td>
                <td>{{ $d->distributorProfile?->is_main ? 'Yes' : 'No' }}</td>
                <td><span class="badge text-bg-{{ $d->status === \App\Enums\UserStatus::Active ? 'success' : 'secondary' }}">{{ $d->status->value }}</span></td>
                <td>@include('admin.partials.resource-actions', [
                    'view' => route('admin.distributors.show', $d),
                    'edit' => route('admin.distributors.edit', $d),
                    'delete' => $d->distributorProfile?->is_main ? null : route('admin.distributors.destroy', $d),
                    'extra' => '<form method="post" action="'.route('admin.distributors.toggle-status', $d).'" class="d-inline">'.csrf_field().method_field('PATCH').'<button class="btn btn-sm btn-outline-warning">Toggle</button></form>',
                ])</td>
            </tr>
        @empty
            <tr><td colspan="5" class="text-center text-muted py-3">No distributors.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
{{ $distributors->links() }}
@endsection
