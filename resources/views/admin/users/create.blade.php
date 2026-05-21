@extends('layouts.app')
@section('title', 'Add User')
@section('content')
@include('admin.partials.page-header', ['title' => 'Add User', 'actions' => '<a href="'.route('admin.users.index').'" class="btn btn-outline-secondary">Back</a>'])
<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="post" action="{{ route('admin.users.store') }}" enctype="multipart/form-data">
            @csrf
            @include('admin.users._form', compact('roles', 'statuses', 'distributors', 'sponsors'))
            <div class="mt-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary">Create user</button>
                <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@push('head')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const role = document.getElementById('user-role');
    const sponsorField = document.getElementById('sponsor-field');
    const distributorField = document.getElementById('distributor-field');
    const sponsorSelect = document.getElementById('sponsor_id');
    const distributorSelect = document.getElementById('distributor_id');
    const operatorRole = @json(\App\Enums\UserRole::Operator->value);
    const distributorRole = @json(\App\Enums\UserRole::Distributor->value);
    const toggle = () => {
        const r = role?.value;
        const showSponsor = r === operatorRole || r === distributorRole;
        const showDist = r === operatorRole;
        if (sponsorField) sponsorField.style.display = showSponsor ? '' : 'none';
        if (distributorField) distributorField.style.display = showDist ? '' : 'none';
        if (sponsorSelect) sponsorSelect.required = showSponsor;
        if (distributorSelect) distributorSelect.required = showDist;
    };
    role?.addEventListener('change', toggle);
    toggle();
    document.getElementById('btn-gen-password')?.addEventListener('click', () => {
        const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789!@#';
        let p = '';
        for (let i = 0; i < 12; i++) p += chars[Math.floor(Math.random() * chars.length)];
        const input = document.getElementById('user-password');
        if (input) { input.value = p; input.type = 'text'; }
    });
});
</script>
@endpush
@endsection
