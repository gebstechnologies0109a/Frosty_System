@extends('layouts.app')
@section('title', 'Stock Movement Logs')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h1 class="h4 mb-1">Stock Movement Logs</h1>
        <p class="text-muted small mb-0">Complete inventory audit trail</p>
    </div>
    <a href="{{ route('admin.purchasing.products.index') }}" class="btn btn-outline-primary btn-sm">Product catalog</a>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-header bg-white fw-semibold">Filters</div>
    <div class="card-body">
        <form method="get" action="{{ route('admin.purchasing.stock-movements.index') }}" class="row g-3">
            <div class="col-md-3">
                <label class="form-label small">Date from</label>
                <input type="date" name="date_from" class="form-control form-control-sm" value="{{ $filters['date_from'] ?? '' }}">
            </div>
            <div class="col-md-3">
                <label class="form-label small">Date to</label>
                <input type="date" name="date_to" class="form-control form-control-sm" value="{{ $filters['date_to'] ?? '' }}">
            </div>
            <div class="col-md-3">
                <label class="form-label small">Product</label>
                <select name="product_id" class="form-select form-select-sm">
                    <option value="">All products</option>
                    @foreach ($products as $p)
                        <option value="{{ $p->id }}" @selected(($filters['product_id'] ?? '') == $p->id)>{{ $p->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small">Action type</label>
                <select name="action_type" class="form-select form-select-sm">
                    <option value="">All actions</option>
                    @foreach ($actionTypes as $type)
                        <option value="{{ $type }}" @selected(($filters['action_type'] ?? '') === $type)>
                            {{ str_replace('_', ' ', ucfirst($type)) }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small">User</label>
                <select name="user_id" class="form-select form-select-sm">
                    <option value="">All users</option>
                    @foreach ($users as $u)
                        <option value="{{ $u->id }}" @selected(($filters['user_id'] ?? '') == $u->id)>{{ $u->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small">Quantity change</label>
                <select name="quantity_direction" class="form-select form-select-sm">
                    <option value="">Any</option>
                    <option value="positive" @selected(($filters['quantity_direction'] ?? '') === 'positive')>Positive (+)</option>
                    <option value="negative" @selected(($filters['quantity_direction'] ?? '') === 'negative')>Negative (−)</option>
                    <option value="zero" @selected(($filters['quantity_direction'] ?? '') === 'zero')>Zero</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small">Category</label>
                <select name="category" class="form-select form-select-sm">
                    <option value="">All categories</option>
                    @foreach ($categories as $cat)
                        <option value="{{ $cat->value }}" @selected(($filters['category'] ?? '') === $cat->value)>{{ $cat->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm">Apply filters</button>
                <a href="{{ route('admin.purchasing.stock-movements.index') }}" class="btn btn-outline-secondary btn-sm">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>Timestamp</th>
                    <th>Product</th>
                    <th>Action</th>
                    <th>User</th>
                    <th class="text-end">Change</th>
                    <th class="text-end">Before</th>
                    <th class="text-end">After</th>
                    <th>Description</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            @forelse ($movements as $m)
                <tr>
                    <td class="text-nowrap small">{{ $m->created_at->format('Y-m-d H:i') }}</td>
                    <td>{{ $m->product?->name ?? '—' }}</td>
                    <td><span class="badge text-bg-secondary">{{ $m->actionLabel() }}</span></td>
                    <td class="small">{{ $m->user?->name ?? 'System' }}</td>
                    <td class="text-end {{ $m->quantity_change > 0 ? 'text-success' : ($m->quantity_change < 0 ? 'text-danger' : '') }}">
                        {{ $m->quantity_change > 0 ? '+' : '' }}{{ $m->quantity_change }}
                    </td>
                    <td class="text-end">{{ $m->stock_before }}</td>
                    <td class="text-end fw-semibold">{{ $m->stock_after }}</td>
                    <td class="small text-muted">{{ Str::limit($m->description, 60) }}</td>
                    <td><a href="{{ route('admin.purchasing.stock-movements.show', $m) }}" class="btn btn-sm btn-outline-primary">View</a></td>
                </tr>
            @empty
                <tr><td colspan="9" class="text-center text-muted py-4">No stock movements found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if ($movements->hasPages())
        <div class="card-footer">@include('partials.list-pagination', ['paginator' => $movements])</div>
    @endif
</div>
@endsection
