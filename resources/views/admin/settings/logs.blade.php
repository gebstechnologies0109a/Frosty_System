@extends('layouts.app')
@section('title', 'Activity Logs')
@section('content')
<h1 class="h4 mb-3">Activity Logs</h1>
<table class="table table-sm bg-white shadow-sm">
    <thead><tr><th>When</th><th>User</th><th>Action</th></tr></thead>
    <tbody>
    @foreach ($logs as $log)
        <tr>
            <td>{{ $log->created_at->format('Y-m-d H:i') }}</td>
            <td>{{ $log->user?->name ?? '—' }}</td>
            <td>{{ $log->action }} @if($log->meta)<span class="text-muted small">{{ json_encode($log->meta) }}</span>@endif</td>
        </tr>
    @endforeach
    </tbody>
</table>
{{ $logs->links() }}
@endsection
