@extends('layouts.app')
@section('title', 'Users')
@section('content')
@include('admin.partials.page-header', [
    'title' => 'Users',
    'subtitle' => 'All system accounts',
    'actions' => '<a href="'.route('admin.users.create').'" class="btn btn-primary">Add User</a>',
])
<form method="get" class="card border-0 shadow-sm mb-3">
    <div class="card-body row g-2 align-items-end">
        <div class="col-md-4"><label class="form-label small">Search</label><input name="q" class="form-control form-control-sm" value="{{ request('q') }}" placeholder="Name or email"></div>
        <div class="col-md-3"><label class="form-label small">Role</label>
            <select name="role" class="form-select form-select-sm"><option value="">All</option>
                @foreach ($roles as $val => $label)<option value="{{ $val }}" @selected(request('role') === $val)>{{ $label }}</option>@endforeach
            </select>
        </div>
        <div class="col-md-3"><label class="form-label small">Status</label>
            <select name="status" class="form-select form-select-sm"><option value="">All</option>
                @foreach ($statuses as $s)<option value="{{ $s->value }}" @selected(request('status') === $s->value)>{{ ucfirst($s->value) }}</option>@endforeach
            </select>
        </div>
        <div class="col-auto"><button class="btn btn-primary btn-sm">Filter</button> <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary btn-sm">Reset</a></div>
    </div>
</form>
<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr><th></th><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Created</th><th class="text-end">Actions</th></tr>
            </thead>
            <tbody>
            @forelse ($users as $u)
                <tr>
                    <td>@include('admin.partials.user-avatar', ['user' => $u, 'size' => 36])</td>
                    <td class="fw-medium">{{ $u->displayName() }}</td>
                    <td>{{ $u->email }}</td>
                    <td>{{ $u->role?->label() ?? $u->role }}</td>
                    <td><span class="badge text-bg-{{ $u->status === \App\Enums\UserStatus::Active ? 'success' : 'secondary' }}">{{ $u->status->value }}</span></td>
                    <td class="small text-muted">{{ $u->created_at->format('M j, Y') }}</td>
                    <td class="text-end">
                        @include('admin.partials.user-actions-dropdown', ['u' => $u])
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-muted py-4">No users found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
{{ $users->links() }}
@endsection
