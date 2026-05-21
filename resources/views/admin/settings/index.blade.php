@extends('layouts.app')
@section('title', 'Settings')
@section('content')
<h1 class="h4 mb-3">System settings</h1>
<p><a href="{{ route('admin.settings.logs') }}">Activity logs</a></p>
<form method="post" action="{{ route('admin.settings.update') }}" class="card p-4 col-lg-6">
    @csrf
    <div class="mb-3"><label class="form-label">Qualification points / month</label><input type="number" name="qualification_points" class="form-control" value="{{ $settings['qualification_points'] }}"></div>
    <div class="mb-3"><label class="form-label">Peso per point (₱1 = 1 pt)</label><input type="number" step="0.01" name="peso_per_point" class="form-control" value="{{ $settings['peso_per_point'] }}"></div>
    @for ($i = 1; $i <= 4; $i++)
        <div class="mb-3"><label class="form-label">Override L{{ $i }} %</label><input type="number" step="0.01" name="override_level_{{ $i }}_percent" class="form-control" value="{{ $settings['override_level_'.$i.'_percent'] }}"></div>
    @endfor
    <button class="btn btn-primary">Save</button>
</form>
@endsection
