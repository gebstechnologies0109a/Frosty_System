@extends('layouts.app')
@section('title', 'Purchasing — Products')
@section('content')
@php
    $bulkRoutes = $canBulkManage ? [
        'update' => route('admin.purchasing.products.bulk-update'),
        'delete' => route('admin.purchasing.products.bulk-delete'),
        'price' => route('admin.purchasing.products.bulk-price-update'),
        'category' => route('admin.purchasing.products.bulk-category-update'),
        'inventory' => route('admin.purchasing.products.bulk-inventory-update'),
    ] : [];
@endphp
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h1 class="h4 mb-0">Product catalog</h1>
    <div class="d-flex gap-2 flex-wrap">
        @if ($canBulkManage)
            <button type="button" class="btn btn-outline-primary" id="toggle-bulk-mode">Bulk Actions</button>
            <div class="btn-group">
                <button type="button" class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">Export products</button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="{{ route('admin.purchasing.products.export', array_merge(request()->query(), ['format' => 'csv'])) }}">CSV — all</a></li>
                    <li><a class="dropdown-item" href="{{ route('admin.purchasing.products.export', array_merge(request()->query(), ['format' => 'xlsx'])) }}">Excel — all</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="{{ route('admin.purchasing.products.export', array_merge(request()->query(), ['format' => 'csv', 'filtered' => 1])) }}">CSV — filtered</a></li>
                    <li><a class="dropdown-item" href="{{ route('admin.purchasing.products.export', array_merge(request()->query(), ['format' => 'xlsx', 'filtered' => 1])) }}">Excel — filtered</a></li>
                </ul>
            </div>
            <button type="button" class="btn btn-outline-success" data-bs-toggle="modal" data-bs-target="#importProductsModal">Import products</button>
        @endif
        <a href="{{ route('admin.purchasing.products.create') }}" class="btn btn-primary">Add product</a>
    </div>
</div>
<p class="small text-muted mb-3">Regional pricing is stored per product (Luzon, Davao, Tacloban). Orders use the placer’s region.</p>

@include('admin.purchasing.products._filters')

@if ($hasActiveFilters)
    <p class="small text-muted mb-3">
        Showing filtered results.
        <a href="{{ route('admin.purchasing.products.index') }}">Clear all filters</a>
    </p>
@endif

@if ($canBulkManage)
<div id="bulk-toolbar" class="card mb-4 d-none border-primary">
    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center py-2">
        <span class="fw-semibold">Bulk mode — <span id="selected-count">0</span> selected</span>
        <button type="button" class="btn btn-sm btn-light" id="exit-bulk-mode">Exit bulk mode</button>
    </div>
    <div class="card-body p-0">
        <div class="d-flex align-items-center gap-3 px-3 py-2 border-bottom bg-light">
            <div class="form-check mb-0">
                <input class="form-check-input" type="checkbox" id="select-all-global">
                <label class="form-check-label" for="select-all-global">Select all products</label>
            </div>
        </div>
        <ul class="nav nav-tabs px-3 pt-2" role="tablist">
            <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-bulk-edit" type="button">Bulk Edit</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-bulk-price" type="button">Bulk Price Update</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-bulk-category" type="button">Bulk Category Change</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-bulk-inventory" type="button">Bulk Inventory Adjustment</button></li>
            <li class="nav-item"><button class="nav-link text-danger" data-bs-toggle="tab" data-bs-target="#tab-bulk-delete" type="button">Bulk Delete</button></li>
        </ul>
        <div class="tab-content p-3">
            <div class="tab-pane fade show active" id="tab-bulk-edit">
                <p class="small text-muted">Only filled fields are applied. Empty fields leave existing values unchanged.</p>
                <form id="form-bulk-edit" class="row g-3 align-items-end">
                    @csrf
                    <div class="col-md-3">
                        <label class="form-label">Category</label>
                        <select name="category" class="form-select form-select-sm">
                            <option value="">— no change —</option>
                            @foreach ($productCategories as $cat)
                                <option value="{{ $cat->value }}">{{ $cat->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Points</label>
                        <input type="number" name="points" class="form-control form-control-sm" min="0" placeholder="—">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select form-select-sm">
                            <option value="">— no change —</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Luzon ₱</label>
                        <input type="number" step="0.01" min="0" name="price_luzon" class="form-control form-control-sm" placeholder="—">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Davao ₱</label>
                        <input type="number" step="0.01" min="0" name="price_davao" class="form-control form-control-sm" placeholder="—">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Tacloban ₱</label>
                        <input type="number" step="0.01" min="0" name="price_tacloban" class="form-control form-control-sm" placeholder="—">
                    </div>
                    <div class="col-md-12">
                        <button type="submit" class="btn btn-primary btn-sm bulk-submit" disabled>Apply bulk edit</button>
                    </div>
                </form>
            </div>
            <div class="tab-pane fade" id="tab-bulk-price">
                <p class="small text-muted">Use a percentage to adjust all regions, and/or set manual prices for specific regions only. Points are never changed here.</p>
                <form id="form-bulk-price" class="row g-3 align-items-end">
                    @csrf
                    <div class="col-md-2">
                        <label class="form-label">% change</label>
                        <input type="number" step="0.01" name="price_percent" class="form-control form-control-sm" placeholder="e.g. 5 or -3">
                        <div class="form-text">+5 = +5%, -3 = -3%</div>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Luzon ₱</label>
                        <input type="number" step="0.01" min="0" name="price_luzon" class="form-control form-control-sm" placeholder="—">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Davao ₱</label>
                        <input type="number" step="0.01" min="0" name="price_davao" class="form-control form-control-sm" placeholder="—">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Tacloban ₱</label>
                        <input type="number" step="0.01" min="0" name="price_tacloban" class="form-control form-control-sm" placeholder="—">
                    </div>
                    <div class="col-md-12">
                        <button type="submit" class="btn btn-primary btn-sm bulk-submit" disabled>Apply price update</button>
                    </div>
                </form>
            </div>
            <div class="tab-pane fade" id="tab-bulk-category">
                <form id="form-bulk-category" class="row g-3 align-items-end">
                    @csrf
                    <div class="col-md-4">
                        <label class="form-label">New category</label>
                        <select name="category" class="form-select form-select-sm" required>
                            @foreach ($productCategories as $cat)
                                <option value="{{ $cat->value }}">{{ $cat->label() }}</option>
                            @endforeach
                        </select>
                        <div class="form-text">Softserve → 2 pts; all others → 0 pts.</div>
                    </div>
                    <div class="col-md-12">
                        <button type="submit" class="btn btn-primary btn-sm bulk-submit" disabled>Apply category change</button>
                    </div>
                </form>
            </div>
            <div class="tab-pane fade" id="tab-bulk-inventory">
                <form id="form-bulk-inventory" class="row g-3 align-items-end">
                    @csrf
                    <div class="col-md-4">
                        <label class="form-label">Adjustment type</label>
                        <select name="adjustment_type" class="form-select form-select-sm" required>
                            <option value="increase">Increase Stock</option>
                            <option value="decrease">Decrease Stock</option>
                            <option value="set">Set Exact Stock Level</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Amount</label>
                        <input type="number" name="amount" class="form-control form-control-sm" min="0" value="0" required>
                    </div>
                    <div class="col-md-12">
                        <button type="submit" class="btn btn-primary btn-sm bulk-submit" disabled>Apply inventory adjustment to selected products</button>
                    </div>
                </form>
            </div>
            <div class="tab-pane fade" id="tab-bulk-delete">
                <p class="text-danger small mb-2">Softserve products cannot be deleted. Products with existing orders are skipped.</p>
                <form id="form-bulk-delete">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger btn-sm bulk-submit" disabled>Delete selected products</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endif

@foreach ($categories as $categoryKey => $products)
    <section class="mb-4 product-category-section" data-category="{{ $categoryKey }}">
        <div class="d-flex align-items-center gap-3 mb-2">
            <h2 class="h5 mb-0">
                <span class="badge text-bg-primary">{{ $categoryLabels[$categoryKey] ?? ucfirst($categoryKey) }}</span>
                <span class="text-muted small fw-normal">({{ $products->count() }})</span>
            </h2>
            @if ($canBulkManage)
                <div class="form-check mb-0 bulk-only d-none">
                    <input class="form-check-input category-select-all" type="checkbox"
                        id="select-all-{{ $categoryKey }}" data-category="{{ $categoryKey }}">
                    <label class="form-check-label small" for="select-all-{{ $categoryKey }}">Select all in category</label>
                </div>
            @endif
        </div>
        <div class="table-responsive bg-white shadow-sm rounded">
            <table class="table table-sm table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        @if ($canBulkManage)
                            <th class="bulk-only d-none" style="width:2.5rem"></th>
                        @endif
                        <th>Name</th>
                        <th>Category</th>
                        <th class="text-end">Points</th>
                        <th class="text-end">Luzon ₱</th>
                        <th class="text-end">Davao ₱</th>
                        <th class="text-end">Tacloban ₱</th>
                        <th class="text-end">Inventory</th>
                        <th>Status</th>
                        <th class="text-end normal-only">Actions</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($products as $p)
                    @php $prices = $p->regionalPrices(); @endphp
                    <tr class="product-row"
                        data-product-id="{{ $p->id }}"
                        data-category="{{ $p->category->value }}"
                        data-is-softserve="{{ $p->category === \App\Enums\ProductCategory::Softserve ? '1' : '0' }}">
                        @if ($canBulkManage)
                            <td class="bulk-only d-none">
                                <input type="checkbox" class="form-check-input product-checkbox"
                                    value="{{ $p->id }}" data-category="{{ $categoryKey }}">
                            </td>
                        @endif
                        <td>{{ $p->name }}</td>
                        <td><span class="badge text-bg-light text-dark border">{{ $p->category->label() }}</span></td>
                        <td class="text-end">{{ $p->points }}</td>
                        <td class="text-end">{{ number_format($prices['luzon'] ?? 0, 2) }}</td>
                        <td class="text-end">{{ number_format($prices['davao'] ?? 0, 2) }}</td>
                        <td class="text-end">{{ number_format($prices['tacloban'] ?? 0, 2) }}</td>
                        <td class="text-end fw-semibold">{{ number_format($p->stockLevel()) }}</td>
                        <td>
                            @if ($p->isActive())
                                <span class="badge text-bg-success">Active</span>
                            @else
                                <span class="badge text-bg-secondary">Inactive</span>
                            @endif
                        </td>
                        <td class="text-end text-nowrap normal-only">
                            <a href="{{ route('admin.purchasing.stock-movements.index', ['product_id' => $p->id]) }}" class="btn btn-sm btn-outline-secondary">Stock logs</a>
                            <a href="{{ route('admin.purchasing.products.edit', $p) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                            <form method="post" action="{{ route('admin.purchasing.products.toggle-status', $p) }}" class="d-inline">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="btn btn-sm btn-outline-secondary">
                                    {{ $p->isActive() ? 'Deactivate' : 'Activate' }}
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $canBulkManage ? 10 : 9 }}" class="text-center text-muted py-3">No products in this category.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endforeach

@if ($categories->isEmpty())
    <div class="alert alert-secondary">No products match the current filters.</div>
@endif

@if ($canBulkManage)
<div class="modal fade" id="importProductsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" action="{{ route('admin.purchasing.products.import') }}" enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Import products</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="small text-muted">Upload CSV or Excel (.xlsx). Existing products match by <strong>name</strong> (or product_id). Stock changes are logged automatically.</p>
                    <div class="mb-3">
                        <label class="form-label">File</label>
                        <input type="file" name="file" class="form-control" accept=".csv,.txt,.xlsx,.xls" required>
                    </div>
                    <p class="small mb-0">
                        <a href="{{ route('admin.purchasing.products.import-template', ['format' => 'csv']) }}">Download import template (CSV)</a>
                        ·
                        <a href="{{ route('admin.purchasing.products.import-template', ['format' => 'xlsx']) }}">Excel</a>
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Upload and import</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('filter-search')?.addEventListener('keydown', function (e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        document.getElementById('product-filters-form')?.submit();
    }
});
</script>

@if ($canBulkManage)
<script>
(function () {
    const routes = @json($bulkRoutes);
    const toolbar = document.getElementById('bulk-toolbar');
    const toggleBtn = document.getElementById('toggle-bulk-mode');
    const exitBtn = document.getElementById('exit-bulk-mode');
    const globalSelect = document.getElementById('select-all-global');
    const countEl = document.getElementById('selected-count');
    let bulkActive = false;

    function getSelectedIds() {
        return [...document.querySelectorAll('.product-checkbox:checked')].map(cb => cb.value);
    }

    function updateUi() {
        const ids = getSelectedIds();
        const n = ids.length;
        countEl.textContent = n;
        document.querySelectorAll('.bulk-submit').forEach(btn => { btn.disabled = n === 0; });
        document.querySelectorAll('.category-select-all').forEach(catCb => {
            const cat = catCb.dataset.category;
            const boxes = [...document.querySelectorAll('.product-checkbox[data-category="' + cat + '"]')];
            const checked = boxes.filter(b => b.checked).length;
            catCb.checked = boxes.length > 0 && checked === boxes.length;
            catCb.indeterminate = checked > 0 && checked < boxes.length;
        });
        const allBoxes = [...document.querySelectorAll('.product-checkbox')];
        const allChecked = allBoxes.filter(b => b.checked).length;
        globalSelect.checked = allBoxes.length > 0 && allChecked === allBoxes.length;
        globalSelect.indeterminate = allChecked > 0 && allChecked < allBoxes.length;
    }

    function setBulkMode(on) {
        bulkActive = on;
        toolbar.classList.toggle('d-none', !on);
        document.querySelectorAll('.bulk-only').forEach(el => el.classList.toggle('d-none', !on));
        document.querySelectorAll('.normal-only').forEach(el => el.classList.toggle('d-none', on));
        toggleBtn.textContent = on ? 'Bulk mode active' : 'Bulk Actions';
        toggleBtn.classList.toggle('btn-primary', on);
        toggleBtn.classList.toggle('btn-outline-primary', !on);
        if (!on) {
            document.querySelectorAll('.product-checkbox, .category-select-all, #select-all-global').forEach(cb => {
                cb.checked = false;
                cb.indeterminate = false;
            });
        }
        updateUi();
    }

    toggleBtn.addEventListener('click', () => setBulkMode(!bulkActive));
    exitBtn.addEventListener('click', () => setBulkMode(false));

    globalSelect.addEventListener('change', () => {
        document.querySelectorAll('.product-checkbox').forEach(cb => { cb.checked = globalSelect.checked; });
        updateUi();
    });

    document.querySelectorAll('.category-select-all').forEach(catCb => {
        catCb.addEventListener('change', () => {
            document.querySelectorAll('.product-checkbox[data-category="' + catCb.dataset.category + '"]').forEach(cb => {
                cb.checked = catCb.checked;
            });
            updateUi();
        });
    });

    document.addEventListener('change', e => {
        if (e.target.classList.contains('product-checkbox')) updateUi();
    });

    function appendIds(form) {
        getSelectedIds().forEach(id => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'product_ids[]';
            input.value = id;
            form.appendChild(input);
        });
    }

    function submitBulk(form, url, method, confirmMessage) {
        const ids = getSelectedIds();
        if (ids.length === 0) {
            alert('Select at least one product.');
            return;
        }
        if (confirmMessage && !confirm(confirmMessage.replace('{n}', ids.length))) {
            return;
        }
        if (method === 'DELETE' && !confirm('Delete selected products? Softserve and products with orders will be skipped.')) {
            return;
        }
        const f = document.createElement('form');
        f.method = 'POST';
        f.action = url;
        const token = form.querySelector('input[name="_token"]');
        if (token) {
            const t = document.createElement('input');
            t.type = 'hidden';
            t.name = '_token';
            t.value = token.value;
            f.appendChild(t);
        }
        if (method === 'DELETE') {
            const m = document.createElement('input');
            m.type = 'hidden';
            m.name = '_method';
            m.value = 'DELETE';
            f.appendChild(m);
        }
        [...form.querySelectorAll('input, select')].forEach(el => {
            if (el.name && el.name !== '_token' && el.name !== '_method' && el.value !== '') {
                const i = document.createElement('input');
                i.type = 'hidden';
                i.name = el.name;
                i.value = el.value;
                f.appendChild(i);
            }
        });
        appendIds(f);
        document.body.appendChild(f);
        f.submit();
    }

    document.getElementById('form-bulk-edit').addEventListener('submit', e => {
        e.preventDefault();
        submitBulk(e.target, routes.update, 'POST');
    });
    document.getElementById('form-bulk-price').addEventListener('submit', e => {
        e.preventDefault();
        submitBulk(e.target, routes.price, 'POST');
    });
    document.getElementById('form-bulk-category').addEventListener('submit', e => {
        e.preventDefault();
        if (!confirm('Change category for all selected products? Points will be set to 2 for softserve, 0 otherwise.')) return;
        submitBulk(e.target, routes.category, 'POST');
    });
    document.getElementById('form-bulk-inventory').addEventListener('submit', e => {
        e.preventDefault();
        submitBulk(e.target, routes.inventory, 'POST', 'Are you sure you want to adjust inventory for {n} products?');
    });
    document.getElementById('form-bulk-delete').addEventListener('submit', e => {
        e.preventDefault();
        submitBulk(e.target, routes.delete, 'DELETE');
    });
})();
</script>
@endif
@endsection
