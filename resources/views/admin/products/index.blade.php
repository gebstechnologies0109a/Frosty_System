@extends('layouts.app')
@section('title', 'Products')
@section('content')
@include('admin.partials.page-header', [
    'title' => 'Products',
    'actions' => ($canBulkEdit ?? false)
        ? '<button type="button" class="btn btn-warning" id="bulkEditBtn" disabled>Bulk Edit</button>
            <a href="'.route('admin.products.create').'" class="btn btn-primary">Add Product</a>'
        : '<a href="'.route('admin.products.create').'" class="btn btn-primary">Add Product</a>',
])
<form method="get" class="card border-0 shadow-sm mb-3"><div class="card-body row g-2 align-items-end">
    <div class="col-md-4"><label class="form-label small">Search</label><input name="q" class="form-control form-control-sm" value="{{ request('q') }}"></div>
    <div class="col-md-3"><label class="form-label small">Category</label><select name="category" class="form-select form-select-sm"><option value="">All</option>@foreach ($categories as $c)<option value="{{ $c->value }}" @selected(request('category') === $c->value)>{{ $c->label() }}</option>@endforeach</select></div>
    <div class="col-md-2"><label class="form-label small">Status</label><select name="status" class="form-select form-select-sm"><option value="">All</option><option value="active" @selected(request('status') === 'active')>Active</option><option value="inactive" @selected(request('status') === 'inactive')>Inactive</option></select></div>
    <div class="col-auto"><button class="btn btn-primary btn-sm">Filter</button> <a href="{{ route('admin.products.index') }}" class="btn btn-outline-secondary btn-sm">Reset</a></div>
</div></form>
<div class="card border-0 shadow-sm">
    <table class="table table-hover mb-0">
        <thead class="table-light">
            <tr>
                @if ($canBulkEdit ?? false)
                    <th style="width: 2.5rem;"><input type="checkbox" id="selectAll" class="form-check-input" aria-label="Select all products on this page"></th>
                @endif
                <th>Name</th>
                <th>Category</th>
                <th>Stock</th>
                <th>Status</th>
                <th class="text-end">Actions</th>
            </tr>
        </thead>
        <tbody>
        @forelse ($products as $p)
            <tr>
                @if ($canBulkEdit ?? false)
                    <td><input type="checkbox" class="form-check-input product-checkbox" value="{{ $p->id }}" aria-label="Select {{ $p->name }}"></td>
                @endif
                <td class="fw-medium">{{ $p->name }}</td>
                <td>{{ $p->category->label() }}</td>
                <td>{{ $p->stockLevel() }}</td>
                <td><span class="badge text-bg-{{ $p->isActive() ? 'success' : 'secondary' }}">{{ $p->status }}</span></td>
                <td>@include('admin.partials.resource-actions', [
                    'view' => route('admin.products.show', $p),
                    'edit' => route('admin.products.edit', $p),
                    'delete' => route('admin.products.destroy', $p),
                    'extra' => '<form method="post" action="'.route('admin.products.toggle-status', $p).'" class="d-inline">'.csrf_field().method_field('PATCH').'<button class="btn btn-sm btn-outline-warning">Toggle</button></form>',
                ])</td>
            </tr>
        @empty
            <tr><td colspan="{{ ($canBulkEdit ?? false) ? 6 : 5 }}" class="text-center text-muted py-3">No products.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@include('partials.list-pagination', ['paginator' => $products])
@if ($canBulkEdit ?? false)
    @include('admin.products.bulk-edit')
@endif
@endsection
@if ($canBulkEdit ?? false)
@push('scripts')
<script>
(function () {
    const selectAll = document.getElementById('selectAll');
    const bulkEditBtn = document.getElementById('bulkEditBtn');
    const bulkEditForm = document.getElementById('bulkEditForm');
    const bulkEditModalEl = document.getElementById('bulkEditModal');
    const bulkEditProductIds = document.getElementById('bulkEditProductIds');
    const bulkEditSelectedCount = document.getElementById('bulkEditSelectedCount');
    const checkboxes = () => [...document.querySelectorAll('.product-checkbox')];

    function selectedIds() {
        return checkboxes().filter(cb => cb.checked).map(cb => cb.value);
    }

    function updateBulkUi() {
        const ids = selectedIds();
        const count = ids.length;
        if (bulkEditBtn) {
            bulkEditBtn.disabled = count === 0;
        }
        if (selectAll) {
            const all = checkboxes();
            selectAll.checked = all.length > 0 && all.every(cb => cb.checked);
            selectAll.indeterminate = count > 0 && count < all.length;
        }
    }

    if (selectAll) {
        selectAll.addEventListener('change', () => {
            checkboxes().forEach(cb => { cb.checked = selectAll.checked; });
            updateBulkUi();
        });
    }

    checkboxes().forEach(cb => cb.addEventListener('change', updateBulkUi));

    if (bulkEditBtn && bulkEditModalEl) {
        const bulkEditModal = new bootstrap.Modal(bulkEditModalEl);
        bulkEditBtn.addEventListener('click', () => {
            const ids = selectedIds();
            if (ids.length === 0) {
                return;
            }
            bulkEditProductIds.innerHTML = '';
            ids.forEach(id => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'product_ids[]';
                input.value = id;
                bulkEditProductIds.appendChild(input);
            });
            bulkEditSelectedCount.textContent = String(ids.length);
            bulkEditModal.show();
        });
    }

    if (bulkEditForm) {
        bulkEditForm.addEventListener('submit', e => {
            const form = e.target;
            const hasField = [...form.querySelectorAll('select, input[type="number"]')].some(el => {
                if (!el.name || el.name === '_token') {
                    return false;
                }
                return String(el.value).trim() !== '';
            });
            if (!hasField) {
                e.preventDefault();
                alert('Fill at least one field to update (category, status, points, or a regional price).');
            }
        });
    }

    updateBulkUi();
})();
</script>
@endpush
@endif
