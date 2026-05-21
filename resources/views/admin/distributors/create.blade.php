@extends('layouts.app')
@section('title', 'Add Distributor')
@section('content')
@include('admin.partials.page-header', [
    'title' => 'Add Distributor',
    'subtitle' => 'Create a distributor account and pricing region',
])
<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="post" action="{{ route('admin.distributors.store') }}">
            @csrf
            <div class="mb-3">
                <label class="form-label">Name</label>
                <input type="text" name="name" class="form-control" value="{{ old('name') }}" required autocomplete="organization">
            </div>
            <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" value="{{ old('email') }}" required autocomplete="email">
            </div>
            <div class="mb-3">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" required minlength="8" autocomplete="new-password">
            </div>
            @include('admin.distributors._pricing_region', [
                'pricingRegions' => $pricingRegions,
                'selected' => old('pricing_region', \App\Enums\DistributorPricingRegion::Luzon->value),
            ])
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">Create distributor</button>
                <a href="{{ route('admin.distributors.index') }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
