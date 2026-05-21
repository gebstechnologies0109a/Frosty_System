@extends('layouts.app')
@section('title', 'Add User')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Add User</h1>
    <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-secondary">Back to Dashboard</a>
</div>

<form method="post" action="{{ route('admin.users.store') }}" class="card p-4 col-lg-8">
    @csrf

    <div class="mb-3">
        <label for="name" class="form-label">Full name</label>
        <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror"
               value="{{ old('name') }}" required autofocus>
        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="mb-3">
        <label for="email" class="form-label">Email address</label>
        <input type="email" name="email" id="email" class="form-control @error('email') is-invalid @enderror"
               value="{{ old('email') }}" required>
        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="mb-3">
        <label for="role" class="form-label">Access level</label>
        <select name="role" id="role" class="form-select @error('role') is-invalid @enderror" required>
            <option value="" disabled {{ old('role') ? '' : 'selected' }}>Select access level</option>
            @foreach ($roles as $value => $label)
                <option value="{{ $value }}" @selected(old('role') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        @error('role')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="mb-3" id="distributor-field" style="display: none;">
        <label for="distributor_id" class="form-label">Distributor</label>
        <select name="distributor_id" id="distributor_id" class="form-select @error('distributor_id') is-invalid @enderror">
            <option value="">Select distributor</option>
            @foreach ($distributors as $distributor)
                <option value="{{ $distributor->id }}" @selected((string) old('distributor_id') === (string) $distributor->id)>
                    {{ $distributor->name }}
                </option>
            @endforeach
        </select>
        @error('distributor_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
        <div class="form-text">Required when access level is Operator.</div>
    </div>

    <div class="mb-4">
        <label for="password" class="form-label">Password</label>
        <input type="password" name="password" id="password" class="form-control @error('password') is-invalid @enderror"
               required autocomplete="new-password">
        @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary">Create user</button>
        <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-secondary">Cancel</a>
    </div>
</form>

@push('head')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const role = document.getElementById('role');
        const distributorField = document.getElementById('distributor-field');
        const distributorSelect = document.getElementById('distributor_id');
        const operatorRole = @json(\App\Enums\UserRole::Operator->value);

        function toggleDistributor() {
            const show = role.value === operatorRole;
            distributorField.style.display = show ? '' : 'none';
            distributorSelect.required = show;
            if (!show) {
                distributorSelect.value = '';
            }
        }

        role.addEventListener('change', toggleDistributor);
        toggleDistributor();
    });
</script>
@endpush
@endsection
