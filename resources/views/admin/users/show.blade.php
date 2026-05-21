@extends('layouts.app')
@section('title', $user->displayName())
@section('content')
@include('admin.partials.page-header', [
    'title' => $user->displayName(),
    'subtitle' => $user->email,
    'actions' => view('admin.partials.user-show-actions', ['user' => $user])->render(),
])
<div class="d-flex align-items-center gap-3 mb-4">
    @include('admin.partials.user-avatar', ['user' => $user, 'size' => 64])
    <div>
        <div class="fw-bold">{{ $user->role?->label() }}</div>
        <span class="badge text-bg-{{ $user->status === \App\Enums\UserStatus::Active ? 'success' : 'secondary' }}">{{ $user->status->value }}</span>
        <span class="small text-muted ms-2">Joined {{ $user->created_at->format('M j, Y') }}</span>
    </div>
</div>

<ul class="nav nav-tabs mb-3" role="tablist">
    <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-overview" type="button">Overview</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-activity" type="button">Activity Logs</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-related" type="button">Related Data</button></li>
</ul>

<div class="tab-content">
    <div class="tab-pane fade show active" id="tab-overview">
        <div class="card border-0 shadow-sm">
            <div class="card-body row g-3">
                <div class="col-md-6"><div class="text-muted small">First name</div><div class="fw-semibold">{{ $user->first_name }}</div></div>
                <div class="col-md-6"><div class="text-muted small">Last name</div><div class="fw-semibold">{{ $user->last_name ?: '—' }}</div></div>
                <div class="col-md-6"><div class="text-muted small">Sponsor</div><div class="fw-semibold">{{ $user->sponsor?->displayName() ?? '—' }}</div></div>
                @if ($user->isOperator())
                <div class="col-md-6"><div class="text-muted small">Distributor</div><div class="fw-semibold">{{ $user->assignedDistributor?->name ?? '—' }}</div></div>
                <div class="col-md-6"><div class="text-muted small">Region</div><div class="fw-semibold">{{ $user->region?->label() }}</div></div>
                @endif
                @if ($user->wallet)
                <div class="col-md-6"><div class="text-muted small">Wallet balance</div><div class="fw-semibold">₱{{ number_format($user->wallet->balance, 2) }}</div></div>
                @endif
            </div>
        </div>
    </div>
    <div class="tab-pane fade" id="tab-activity">
        <div class="card border-0 shadow-sm">
            <table class="table table-sm mb-0">
                <thead class="table-light"><tr><th>When</th><th>Action</th><th>IP</th></tr></thead>
                <tbody>
                @forelse ($activityLogs as $log)
                    <tr>
                        <td class="small">{{ $log->created_at->format('M j, Y H:i') }}</td>
                        <td>{{ $log->action }}</td>
                        <td class="small text-muted">{{ $log->ip_address ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="text-center text-muted py-3">No activity logged.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @include('partials.list-pagination', ['paginator' => $activityLogs])
    </div>
    <div class="tab-pane fade" id="tab-related">
        @if ($user->isOperator())
            <div class="list-group shadow-sm">
                <a href="{{ route('admin.operators.inventory', $user) }}" class="list-group-item list-group-item-action">Inventory</a>
                <a href="{{ route('admin.operators.store-menu', $user) }}" class="list-group-item list-group-item-action">Store menu</a>
                <a href="{{ route('admin.operators.pos-logs', $user) }}" class="list-group-item list-group-item-action">POS logs</a>
                <a href="{{ route('admin.operators.daily-closings', $user) }}" class="list-group-item list-group-item-action">Daily closings</a>
            </div>
            @if ($referredOperators->isNotEmpty())
                <h6 class="mt-4 fw-semibold">Referred operators</h6>
                <ul class="list-group shadow-sm">
                    @foreach ($referredOperators as $ref)
                        <li class="list-group-item d-flex justify-content-between">
                            <span>{{ $ref->displayName() }}</span>
                            <a href="{{ route('admin.users.show', $ref) }}" class="small">View</a>
                        </li>
                    @endforeach
                </ul>
            @endif
        @elseif ($user->isDistributor())
            <a href="{{ route('admin.distributors.orders', $user) }}" class="btn btn-outline-primary btn-sm mb-3">View orders</a>
            <h6 class="fw-semibold">Referred operators</h6>
            <ul class="list-group shadow-sm">
                @forelse ($referredOperators as $op)
                    <li class="list-group-item d-flex justify-content-between">
                        <span>{{ $op->displayName() }} — {{ $op->email }}</span>
                        <a href="{{ route('admin.users.show', $op) }}" class="small">View</a>
                    </li>
                @empty
                    <li class="list-group-item text-muted">No referred operators assigned yet.</li>
                @endforelse
            </ul>
        @else
            <p class="text-muted">No role-specific related data for this account.</p>
        @endif
    </div>
</div>

<div class="modal fade" id="changeRoleModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="post" action="{{ route('admin.users.change-role', $user) }}" class="modal-content">
            @csrf @method('PATCH')
            <div class="modal-header"><h5 class="modal-title">Change role</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <select name="role" class="form-select" required>
                    @foreach (\App\Enums\UserRole::cases() as $r)
                        <option value="{{ $r->value }}" @selected($user->role === $r)>{{ $r->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Save role</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="deleteUserModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="post" action="{{ route('admin.users.destroy', $user) }}" class="modal-content">
            @csrf @method('DELETE')
            <div class="modal-header"><h5 class="modal-title">Delete user</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <p>This permanently deletes <strong>{{ $user->displayName() }}</strong>. Type <strong>DELETE</strong> to confirm.</p>
                <input type="text" name="confirm_delete" class="form-control" autocomplete="off" required placeholder="DELETE">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-danger">Delete user</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="resetPasswordModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="post" action="{{ route('admin.users.reset-password', $user) }}" class="modal-content">
            @csrf
            <div class="modal-header"><h5 class="modal-title">Reset password</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" id="modal-auto-pw">
                    <label class="form-check-label" for="modal-auto-pw">Auto-generate password</label>
                </div>
                <input type="text" name="password" id="modal-password" class="form-control" required>
                <input type="text" name="password_confirmation" id="modal-password-confirm" class="form-control mt-2" required placeholder="Confirm password">
                <button type="button" class="btn btn-sm btn-outline-secondary mt-2" id="modal-gen-pw">Generate</button>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-warning">Reset password</button>
            </div>
        </form>
    </div>
</div>
@push('head')
<script>
document.getElementById('modal-gen-pw')?.addEventListener('click', () => {
    const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789!@#';
    let p = '';
    for (let i = 0; i < 12; i++) p += chars[Math.floor(Math.random() * chars.length)];
    document.getElementById('modal-password').value = p;
    document.getElementById('modal-password-confirm').value = p;
});
document.getElementById('modal-auto-pw')?.addEventListener('change', (e) => {
    if (e.target.checked) document.getElementById('modal-gen-pw')?.click();
});
</script>
@endpush
<a href="{{ route('admin.users.index') }}" class="btn btn-link mt-3">Back to users</a>
@endsection
