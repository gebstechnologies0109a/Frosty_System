@extends('layouts.app')
@section('title', 'Edit Distributor')
@section('content')
@include('admin.partials.page-header', ['title' => 'Edit '.$distributor->name])
<div class="card border-0 shadow-sm"><div class="card-body">
    <form method="post" action="{{ route('admin.distributors.update', $distributor) }}">@csrf @method('PUT')
        <div class="mb-3"><label class="form-label">Name</label><input name="name" class="form-control" value="{{ old('name', $distributor->name) }}" required></div>
        <div class="mb-3"><label class="form-label">Email</label><input type="email" name="email" class="form-control" value="{{ old('email', $distributor->email) }}" required></div>
        <div class="mb-3"><label class="form-label">Status</label>
            <select name="status" class="form-select">@foreach ($statuses as $s)<option value="{{ $s->value }}" @selected(old('status', $distributor->status->value) === $s->value)>{{ ucfirst($s->value) }}</option>@endforeach</select>
        </div>
        @include('admin.distributors._pricing_region', [
            'pricingRegions' => $pricingRegions,
            'selected' => old('pricing_region', $profile?->pricing_region?->value ?? 'luzon'),
        ])
        @if ($profile && ! $profile->is_main)
            <div class="form-check mb-3"><input type="checkbox" name="is_main" value="1" class="form-check-input" id="is_main" @checked(old('is_main', $profile->is_main))><label class="form-check-label" for="is_main">Main distributor</label></div>
        @endif
        <button class="btn btn-primary">Save</button>
        <a href="{{ route('admin.distributors.show', $distributor) }}" class="btn btn-outline-secondary">Cancel</a>
    </form>
</div></div>
@endsection
