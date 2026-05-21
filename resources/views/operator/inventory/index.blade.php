@extends('layouts.app')
@section('title', 'Supplies Inventory')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h1 class="h3 mb-1">Supplies Inventory</h1>
        <p class="text-muted small mb-0">Track store supplies — internal use only (not your customer menu)</p>
    </div>
    <a href="{{ route('operator.products-for-sale.index') }}" class="btn btn-outline-primary btn-sm">Products for Sale</a>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <form method="get" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label small">Category</label>
                <select name="category" class="form-select form-select-sm">
                    <option value="">All categories</option>
                    @foreach ($categories as $val => $label)
                        <option value="{{ $val }}" @selected(($filters['category'] ?? '') === $val)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label small">Stock status</label>
                <select name="stock_status" class="form-select form-select-sm">
                    <option value="">All</option>
                    <option value="in_stock" @selected(($filters['stock_status'] ?? '') === 'in_stock')>In Stock</option>
                    <option value="low_stock" @selected(($filters['stock_status'] ?? '') === 'low_stock')>Low Stock</option>
                    <option value="out_of_stock" @selected(($filters['stock_status'] ?? '') === 'out_of_stock')>Out of Stock</option>
                </select>
            </div>
            <div class="col-auto d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                <a href="{{ route('operator.supplies-inventory.index') }}" class="btn btn-outline-secondary btn-sm">Reset</a>
            </div>
        </form>
    </div>
</div>

@forelse ($grouped as $group)
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white fw-semibold">{{ $group['label'] }}</div>
        <div class="table-responsive">
            <table class="table table-sm table-striped mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Product</th>
                        <th>Category</th>
                        <th class="text-end">Stock</th>
                        <th class="text-end d-none d-md-table-cell">Min level</th>
                        <th>Status</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                @foreach ($group['items'] as $row)
                    @php $status = $row->stockStatus(); @endphp
                    <tr>
                        <td>{{ $row->product->name }}</td>
                        <td>{{ $row->product->category->label() }}</td>
                        <td class="text-end fw-semibold">{{ number_format($row->stock) }}</td>
                        <td class="text-end d-none d-md-table-cell">{{ $row->minimum_stock ?? '—' }}</td>
                        <td>
                            @if ($status === 'in_stock')
                                <span class="badge text-bg-success">In Stock</span>
                            @elseif ($status === 'low_stock')
                                <span class="badge text-bg-warning">Low Stock</span>
                            @else
                                <span class="badge text-bg-danger">Out of Stock</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <button type="button" class="btn btn-sm btn-outline-primary"
                                data-bs-toggle="modal" data-bs-target="#adjustModal"
                                data-product-id="{{ $row->product_id }}"
                                data-product-name="{{ $row->product->name }}"
                                data-stock="{{ $row->stock }}"
                                data-min="{{ $row->minimum_stock ?? '' }}">
                                Adjust
                            </button>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
@empty
    <div class="alert alert-info">No supply items match your filters.</div>
@endforelse

<div class="modal fade" id="adjustModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="post" action="{{ route('operator.supplies-inventory.adjust') }}" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">Adjust Inventory</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-3">Product: <strong id="adj-product-name">—</strong></p>
                <input type="hidden" name="product_id" id="adj-product-id">
                <div class="mb-3">
                    <label class="form-label">Adjustment type</label>
                    <select name="mode" class="form-select" required>
                        <option value="set">Set stock level</option>
                        <option value="increase">Increase</option>
                        <option value="decrease">Decrease</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Quantity</label>
                    <input type="number" name="amount" class="form-control" min="0" required value="0">
                    <div class="form-text">Current stock: <span id="adj-current-stock">0</span></div>
                </div>
                <div class="mb-0">
                    <label class="form-label">Minimum stock level (optional)</label>
                    <input type="number" name="minimum_stock" id="adj-min-stock" class="form-control" min="0" placeholder="Default: 10">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Save adjustment</button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('adjustModal')?.addEventListener('show.bs.modal', function (e) {
    const btn = e.relatedTarget;
    document.getElementById('adj-product-id').value = btn.dataset.productId;
    document.getElementById('adj-product-name').textContent = btn.dataset.productName;
    document.getElementById('adj-current-stock').textContent = btn.dataset.stock;
    document.getElementById('adj-min-stock').value = btn.dataset.min || '';
});
</script>
@endsection
