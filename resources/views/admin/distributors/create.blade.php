@extends('layouts.app')
@section('title', 'Add Distributor')
@section('content')
@include('admin.partials.page-header', ['title' => 'Add Distributor'])
<div class="card border-0 shadow-sm"><div class="card-body">
    <form method="post" action="{{ route('admin.distributors.store') }}">@csrf
        <div class="mb-3"><label class="form-label">Name</label><input name="name" class="form-control" value="{{ old('name') }}" required></div>
        <div class="mb-3"><label class="form-label">Email</label><input type="email" name="email" class="form-control" value="{{ old('email') }}" required></div>
        <div class="mb-3"><label class="form-label">Password</label><input type="password" name="password" class="form-control" required></div>
        <button class="btn btn-primary">Create</button>
        <a href="{{ route('admin.distributors.index') }}" class="btn btn-outline-secondary">Cancel</a>
    </form>
</div></div>
@endsection
