@extends('layouts.operator')
@section('header_title', 'Add operator')
@section('title', 'Add Operator')
@section('content')
<h1 class="h4 mb-3">Add operator referral</h1>
<form method="post" action="{{ route('operator.referrals.store') }}" class="card p-4 col-lg-6">
    @csrf
    <div class="mb-3"><label class="form-label">Name</label><input name="name" class="form-control" required></div>
    <div class="mb-3"><label class="form-label">Email</label><input type="email" name="email" class="form-control" required></div>
    <div class="mb-3"><label class="form-label">Password</label><input type="password" name="password" class="form-control" required></div>
    <div class="mb-3"><label class="form-label">Confirm password</label><input type="password" name="password_confirmation" class="form-control" required></div>
    <button class="btn btn-primary">Create referral</button>
</form>
@endsection
