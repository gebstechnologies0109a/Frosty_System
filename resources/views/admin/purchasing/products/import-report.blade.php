@extends('layouts.app')
@section('title', 'Import Report')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h4 mb-0">Product import report</h1>
    <a href="{{ route('admin.purchasing.products.index') }}" class="btn btn-outline-primary btn-sm">Back to catalog</a>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card shadow-sm"><div class="card-body">
            <div class="text-muted small">Rows processed</div>
            <div class="fs-4 fw-bold">{{ $report['total_rows'] }}</div>
        </div></div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm border-success"><div class="card-body">
            <div class="text-muted small">Created</div>
            <div class="fs-4 fw-bold text-success">{{ $report['created'] }}</div>
        </div></div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm border-primary"><div class="card-body">
            <div class="text-muted small">Updated</div>
            <div class="fs-4 fw-bold text-primary">{{ $report['updated'] }}</div>
        </div></div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm border-danger"><div class="card-body">
            <div class="text-muted small">Errors</div>
            <div class="fs-4 fw-bold text-danger">{{ $report['errors'] }}</div>
        </div></div>
    </div>
</div>

@if (! empty($report['error_details']))
    <div class="card shadow-sm">
        <div class="card-header bg-white fw-semibold">Error details</div>
        <div class="table-responsive">
            <table class="table table-sm mb-0">
                <thead class="table-light"><tr><th>Row</th><th>Message</th></tr></thead>
                <tbody>
                @foreach ($report['error_details'] as $error)
                    <tr>
                        <td>{{ $error['row'] }}</td>
                        <td>{{ $error['message'] }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
@else
    <div class="alert alert-success">Import completed with no row errors.</div>
@endif
@endsection
