@php
    $isSuperAdmin = auth()->user()?->role === \App\Enums\UserRole::SuperAdmin;
    $canImpersonate = $isSuperAdmin && $u->role !== \App\Enums\UserRole::SuperAdmin && $u->id !== auth()->id();
    $modalId = 'userActions-'.$u->id;
@endphp
<div class="dropdown d-inline-block">
    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">Actions</button>
    <ul class="dropdown-menu dropdown-menu-end">
        <li><a class="dropdown-item" href="{{ route('admin.users.show', $u) }}">View</a></li>
        <li><a class="dropdown-item" href="{{ route('admin.users.edit', $u) }}">Edit</a></li>
        @if ($isSuperAdmin)
            <li><hr class="dropdown-divider"></li>
            <li><button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#resetPw-{{ $u->id }}">Reset password</button></li>
            <li><button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#changeRole-{{ $u->id }}">Change role</button></li>
            <li>
                <form method="post" action="{{ route('admin.users.toggle-status', $u) }}" class="d-inline" onsubmit="return confirm('Toggle account status for {{ $u->displayName() }}?')">
                    @csrf @method('PATCH')
                    <button type="submit" class="dropdown-item">{{ $u->status === \App\Enums\UserStatus::Active ? 'Deactivate' : 'Activate' }}</button>
                </form>
            </li>
            <li>
                <form method="post" action="{{ route('admin.users.force-logout', $u) }}" class="d-inline" onsubmit="return confirm('Force logout all sessions?')">
                    @csrf
                    <button type="submit" class="dropdown-item">Force logout</button>
                </form>
            </li>
            @if ($u->isOperator() || $u->isDistributor())
                <li><a class="dropdown-item" href="{{ route('admin.users.related', $u) }}">View related data</a></li>
            @endif
            @if ($canImpersonate)
                <li>
                    <form method="post" action="{{ route('admin.users.impersonate', $u) }}" onsubmit="return confirm('Impersonate {{ $u->displayName() }}?')">
                        @csrf
                        <button type="submit" class="dropdown-item text-warning">Impersonate user</button>
                    </form>
                </li>
            @endif
            <li><hr class="dropdown-divider"></li>
            <li><button type="button" class="dropdown-item text-danger" data-bs-toggle="modal" data-bs-target="#deleteUser-{{ $u->id }}">Delete user</button></li>
        @endif
    </ul>
</div>

@if ($isSuperAdmin)
<div class="modal fade" id="resetPw-{{ $u->id }}" tabindex="-1">
    <div class="modal-dialog">
        <form method="post" action="{{ route('admin.users.reset-password', $u) }}" class="modal-content">
            @csrf
            <div class="modal-header"><h5 class="modal-title">Reset password</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <p class="small text-muted mb-2">{{ $u->displayName() }} ({{ $u->email }})</p>
                <label class="form-label">New password</label>
                <input type="password" name="password" class="form-control" required minlength="8">
                <label class="form-label mt-2">Confirm</label>
                <input type="password" name="password_confirmation" class="form-control" required>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-warning">Reset password</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="changeRole-{{ $u->id }}" tabindex="-1">
    <div class="modal-dialog">
        <form method="post" action="{{ route('admin.users.change-role', $u) }}" class="modal-content">
            @csrf @method('PATCH')
            <div class="modal-header"><h5 class="modal-title">Change role</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <label class="form-label">Role</label>
                <select name="role" class="form-select" required>
                    @foreach (\App\Enums\UserRole::cases() as $r)
                        <option value="{{ $r->value }}" @selected($u->role === $r)>{{ $r->label() }}</option>
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

<div class="modal fade" id="deleteUser-{{ $u->id }}" tabindex="-1">
    <div class="modal-dialog">
        <form method="post" action="{{ route('admin.users.destroy', $u) }}" class="modal-content">
            @csrf @method('DELETE')
            <div class="modal-header"><h5 class="modal-title">Delete user</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <p>Permanently delete <strong>{{ $u->displayName() }}</strong>. Type <strong>DELETE</strong> to confirm.</p>
                <input type="text" name="confirm_delete" class="form-control" required placeholder="DELETE" autocomplete="off">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-danger">Delete user</button>
            </div>
        </form>
    </div>
</div>
@endif
