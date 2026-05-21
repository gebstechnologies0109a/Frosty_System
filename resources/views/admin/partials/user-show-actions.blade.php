@php $isSuperAdmin = auth()->user()?->role === \App\Enums\UserRole::SuperAdmin; @endphp
<a href="{{ route('admin.users.edit', $user) }}" class="btn btn-outline-secondary">Edit User</a>
@if ($isSuperAdmin)
    <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#resetPasswordModal">Reset Password</button>
    <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#changeRoleModal">Change Role</button>
    <form method="post" action="{{ route('admin.users.toggle-status', $user) }}" class="d-inline" onsubmit="return confirm('Toggle account status?')">
        @csrf @method('PATCH')
        <button type="submit" class="btn btn-outline-secondary">{{ $user->status === \App\Enums\UserStatus::Active ? 'Deactivate' : 'Activate' }}</button>
    </form>
    <form method="post" action="{{ route('admin.users.force-logout', $user) }}" class="d-inline" onsubmit="return confirm('Force logout all sessions?')">
        @csrf
        <button type="submit" class="btn btn-outline-secondary">Force Logout</button>
    </form>
    @if ($user->isOperator() || $user->isDistributor())
        <a href="{{ route('admin.users.related', $user) }}" class="btn btn-outline-info">View Related Data</a>
    @endif
    @if ($user->role !== \App\Enums\UserRole::SuperAdmin && $user->id !== auth()->id())
        <form method="post" action="{{ route('admin.users.impersonate', $user) }}" class="d-inline" onsubmit="return confirm('Impersonate this user?')">
            @csrf
            <button type="submit" class="btn btn-warning">Impersonate User</button>
        </form>
    @endif
    <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteUserModal">Delete User</button>
@endif
