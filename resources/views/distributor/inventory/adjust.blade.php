@extends('layouts.app')
@section('title', 'Inventory Adjustment')

@section('content')
<div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1">Inventory adjustment</h1>
        <p class="text-muted mb-0">{{ $distributor->name }} — submissions require admin approval before stock changes.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('distributor.inventory.index') }}" class="btn btn-outline-secondary btn-sm">← Inventory</a>
        <a href="{{ route('distributor.dashboard') }}" class="btn btn-outline-primary btn-sm">Dashboard</a>
    </div>
</div>

@if (session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

@if ($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="row g-4">
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold">New adjustment</div>
            <div class="card-body">
                <form method="post" action="{{ route('distributor.inventory.adjust.store') }}" id="adjustment-form">
                    @csrf

                    <div class="mb-3">
                        <label for="product_id" class="form-label">Product</label>
                        <select name="product_id" id="product_id" class="form-select @error('product_id') is-invalid @enderror" required>
                            <option value="">Select product…</option>
                            @foreach ($products as $product)
                                <option value="{{ $product['id'] }}" data-stock="{{ $product['stock'] }}" @selected(old('product_id') == $product['id'])>
                                    {{ $product['name'] }} (stock: {{ $product['stock'] }})
                                </option>
                            @endforeach
                        </select>
                        @error('product_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text" id="current-stock-hint">Current main stock: —</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Adjustment type</label>
                        <div class="d-flex gap-3">
                            @foreach (\App\Enums\StockLogAdjustmentType::cases() as $type)
                                <div class="form-check">
                                    <input class="form-check-input @error('adjustment_type') is-invalid @enderror" type="radio" name="adjustment_type" id="type_{{ $type->value }}" value="{{ $type->value }}" @checked(old('adjustment_type', 'add') === $type->value) required>
                                    <label class="form-check-label" for="type_{{ $type->value }}">{{ $type->label() }}</label>
                                </div>
                            @endforeach
                        </div>
                        @error('adjustment_type')
                            <div class="text-danger small">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="quantity" class="form-label">Quantity</label>
                        <input type="number" name="quantity" id="quantity" class="form-control @error('quantity') is-invalid @enderror" min="1" value="{{ old('quantity', 1) }}" required>
                        @error('quantity')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="reason" class="form-label">Reason</label>
                        <select name="reason" id="reason" class="form-select @error('reason') is-invalid @enderror" required>
                            <option value="">Select reason…</option>
                            @foreach (\App\Enums\StockLogReason::cases() as $reason)
                                <option value="{{ $reason->value }}" @selected(old('reason') === $reason->value)>{{ $reason->label() }}</option>
                            @endforeach
                        </select>
                        @error('reason')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="remarks" class="form-label">Remarks</label>
                        <textarea name="remarks" id="remarks" rows="3" class="form-control @error('remarks') is-invalid @enderror" required minlength="3" placeholder="Describe why this adjustment is needed…">{{ old('remarks') }}</textarea>
                        @error('remarks')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <button type="submit" class="btn btn-primary w-100">Submit for approval</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold">Adjustment history</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Product</th>
                                <th>Type</th>
                                <th class="text-end">Qty</th>
                                <th>Status</th>
                                @if (auth()->user()?->role?->isAdmin())
                                    <th></th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($history as $log)
                                <tr>
                                    <td class="text-nowrap small">{{ $log->created_at->format('M j, Y H:i') }}</td>
                                    <td>{{ $log->product?->name }}</td>
                                    <td>{{ $log->adjustment_type->label() }}</td>
                                    <td class="text-end">{{ $log->quantity }}</td>
                                    <td>
                                        @if ($log->isPending())
                                            <span class="badge text-bg-warning">Pending</span>
                                        @else
                                            <span class="badge text-bg-success">Approved</span>
                                            <div class="small text-muted">{{ $log->approver?->name }}</div>
                                        @endif
                                    </td>
                                    @if (auth()->user()?->role?->isAdmin())
                                        <td class="text-end text-nowrap">
                                            @if ($log->isPending())
                                                <form method="post" action="{{ route('admin.purchasing.stock-logs.approve', $log) }}" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-success">Approve</button>
                                                </form>
                                                <form method="post" action="{{ route('admin.purchasing.stock-logs.reject', $log) }}" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">Reject</button>
                                                </form>
                                            @endif
                                        </td>
                                    @endif
                                </tr>
                                <tr>
                                    <td colspan="{{ auth()->user()?->role?->isAdmin() ? 6 : 5 }}" class="small text-muted border-0 pt-0 pb-2">
                                        <strong>Reason:</strong> {{ $log->reason->label() }} — {{ $log->remarks }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ auth()->user()?->role?->isAdmin() ? 6 : 5 }}" class="text-muted p-4">No adjustments yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    const select = document.getElementById('product_id');
    const hint = document.getElementById('current-stock-hint');
    function updateHint() {
        const opt = select.selectedOptions[0];
        hint.textContent = opt?.dataset?.stock !== undefined
            ? 'Current main stock: ' + opt.dataset.stock
            : 'Current main stock: —';
    }
    select?.addEventListener('change', updateHint);
    updateHint();
})();
</script>
@endsection
