@extends('layouts.app')
@section('title', 'Stock Movement Detail')
@section('content')
<div class="mb-3">
    <a href="{{ route('admin.purchasing.stock-movements.index') }}" class="btn btn-outline-secondary btn-sm">&larr; Back to logs</a>
</div>

<div class="card shadow-sm col-lg-8">
    <div class="card-header bg-white fw-semibold">Movement #{{ $movement->id }}</div>
    <div class="card-body">
        <dl class="row mb-0">
            <dt class="col-sm-4">Timestamp</dt>
            <dd class="col-sm-8">{{ $movement->created_at->format('F j, Y g:i A') }}</dd>

            <dt class="col-sm-4">Product</dt>
            <dd class="col-sm-8">
                @if ($movement->product)
                    {{ $movement->product->name }}
                    <span class="badge text-bg-light text-dark border">{{ $movement->product->category->label() }}</span>
                @else
                    <span class="text-muted">Product removed</span>
                @endif
            </dd>

            <dt class="col-sm-4">Action type</dt>
            <dd class="col-sm-8"><code>{{ $movement->action_type }}</code> — {{ $movement->actionLabel() }}</dd>

            <dt class="col-sm-4">Performed by</dt>
            <dd class="col-sm-8">{{ $movement->user?->name ?? 'System' }} @if($movement->user)<span class="text-muted small">({{ $movement->user->email }})</span>@endif</dd>

            <dt class="col-sm-4">Quantity change</dt>
            <dd class="col-sm-8 fw-semibold {{ $movement->quantity_change > 0 ? 'text-success' : ($movement->quantity_change < 0 ? 'text-danger' : '') }}">
                {{ $movement->quantity_change > 0 ? '+' : '' }}{{ $movement->quantity_change }}
            </dd>

            <dt class="col-sm-4">Stock before</dt>
            <dd class="col-sm-8">{{ $movement->stock_before }}</dd>

            <dt class="col-sm-4">Stock after</dt>
            <dd class="col-sm-8 fw-semibold">{{ $movement->stock_after }}</dd>

            <dt class="col-sm-4">Description</dt>
            <dd class="col-sm-8">{{ $movement->description ?? '—' }}</dd>
        </dl>
    </div>
</div>
@endsection
