@extends('layouts.app')
@section('title', 'Edit User')
@section('content')
@include('admin.partials.page-header', ['title' => 'Edit '.$user->displayName()])
<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="post" action="{{ route('admin.users.update', $user) }}" enctype="multipart/form-data">
            @csrf @method('PUT')
            @include('admin.users._form', ['user' => $user, 'statuses' => $statuses, 'distributors' => $distributors, 'sponsors' => $sponsors])
            <div class="mt-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary">Save changes</button>
                <a href="{{ route('admin.users.show', $user) }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@if ($user->isOperator() || $user->isDistributor())
@push('head')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const sponsorField = document.getElementById('sponsor-field');
    if (sponsorField) sponsorField.style.display = '';
});
</script>
@endpush
@endif
@endsection
