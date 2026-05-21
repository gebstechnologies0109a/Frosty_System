@extends('layouts.app')
@section('title', 'Genealogy')
@section('content')
<h1 class="h4 mb-4">Genealogy — {{ $month }}</h1>

<div class="card mb-4 border-primary">
    <div class="card-header fw-semibold">Level 0 — You ({{ $operator->name }})</div>
    <div class="card-body">
        Personal points: <strong>{{ $selfQualification->personal_points }}</strong> / {{ $threshold }}
        — Override eligible:
        @if ($selfQualification->qualified)
            <span class="badge text-bg-success">Yes</span>
        @else
            <span class="badge text-bg-secondary">No</span>
        @endif
    </div>
</div>

@for ($level = 1; $level <= 4; $level++)
    <div class="card mb-3">
        <div class="card-header fw-semibold">Level {{ $level }}</div>
        <table class="table table-sm mb-0">
            <thead><tr><th>Name</th><th>Email</th><th class="text-end">Pts</th><th>Qualified</th></tr></thead>
            <tbody>
            @forelse ($downlines[$level] ?? [] as $user)
                <tr>
                    <td>{{ $user->name }}</td>
                    <td>{{ $user->email }}</td>
                    <td class="text-end">{{ $user->personal_points }} / {{ $threshold }}</td>
                    <td>@if($user->qualified)<span class="badge text-bg-success">Yes</span>@else<span class="badge text-bg-secondary">No</span>@endif</td>
                </tr>
            @empty
                <tr><td colspan="4" class="text-muted text-center">No members</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
@endfor
@endsection
