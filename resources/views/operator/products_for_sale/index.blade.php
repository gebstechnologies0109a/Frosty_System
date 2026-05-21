@extends('layouts.operator')
@section('header_title', 'Store Menu')
@section('title', 'Products for Sale')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h1 class="h3 mb-1">Products for Sale</h1>
        <p class="text-muted small mb-0">Finished goods for your store menu and POS — not raw materials</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('operator.pos.index') }}" class="btn btn-success btn-sm">Frosty POS</a>
        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addProductModal">Add Product for Sale</button>
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-body py-2">
        <form method="get" class="d-flex gap-2 align-items-center">
            <select name="status" class="form-select form-select-sm" style="max-width:160px">
                <option value="">All statuses</option>
                <option value="active" @selected(($filters['status'] ?? '') === 'active')>Active</option>
                <option value="inactive" @selected(($filters['status'] ?? '') === 'inactive')>Inactive</option>
            </select>
            <button type="submit" class="btn btn-sm btn-outline-primary">Filter</button>
        </form>
    </div>
</div>

<div class="row g-3">
@forelse ($items as $item)
    @php $op = $item['model']; @endphp
    <div class="col-md-6 col-lg-4">
        <div class="card shadow-sm h-100 {{ $item['status'] === 'inactive' ? 'opacity-75' : '' }}">
            @if ($item['image_url'])
                <img src="{{ $item['image_url'] }}" class="card-img-top" alt="" style="height:140px;object-fit:cover">
            @endif
            <div class="card-body">
                <div class="d-flex justify-content-between gap-2">
                    <h6 class="card-title mb-1">{{ $item['name'] }}</h6>
                    @if ($item['is_default'])<span class="badge text-bg-info">Default</span>@endif
                </div>
                @if ($item['description'])<p class="small text-muted">{{ $item['description'] }}</p>@endif
                <dl class="row small mb-0">
                    <dt class="col-5">Price</dt><dd class="col-7 text-end fw-bold">₱{{ number_format($item['selling_price'], 2) }}</dd>
                    <dt class="col-5">Cost</dt><dd class="col-7 text-end">{{ $item['cost'] !== null ? '₱'.number_format($item['cost'], 2) : '—' }}</dd>
                    @if ($item['margin'] !== null)
                        <dt class="col-5">Margin</dt><dd class="col-7 text-end text-success">₱{{ number_format($item['margin'], 2) }}</dd>
                    @endif
                </dl>
            </div>
            <div class="card-footer bg-white d-flex gap-2 flex-wrap">
                <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editProductModal"
                    data-id="{{ $op->id }}"
                    data-name="{{ $item['name'] }}"
                    data-description="{{ $item['description'] }}"
                    data-price="{{ $item['selling_price'] }}"
                    data-cost="{{ $item['cost'] }}"
                    data-status="{{ $item['status'] }}"
                    data-has-image="{{ $item['image_url'] ? '1' : '0' }}">Edit</button>
                <form method="post" action="{{ route('operator.products-for-sale.toggle', $op) }}">@csrf
                    <button class="btn btn-sm btn-outline-secondary">{{ $item['status'] === 'active' ? 'Deactivate' : 'Activate' }}</button>
                </form>
            </div>
        </div>
    </div>
@empty
    <div class="col-12"><div class="alert alert-info">No products yet. Defaults will appear after refresh.</div></div>
@endforelse
</div>

<div class="modal fade" id="addProductModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="post" action="{{ route('operator.products-for-sale.store') }}" enctype="multipart/form-data" class="modal-content">
            @csrf
            <div class="modal-header"><h5 class="modal-title">Add Product for Sale</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Product name *</label>
                    <input type="text" name="product_name" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="2"></textarea>
                </div>
                <div class="row g-2">
                    <div class="col-6">
                        <label class="form-label">Price (₱) *</label>
                        <input type="number" name="price" class="form-control" step="0.01" min="0" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label">Cost (₱)</label>
                        <input type="number" name="cost" class="form-control" step="0.01" min="0">
                        <div class="form-text">For P&amp;L</div>
                    </div>
                </div>
                <div class="mb-3 mt-2">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select"><option value="active">Active</option><option value="inactive">Inactive</option></select>
                </div>
                <div class="mb-0">
                    <label class="form-label">Image</label>
                    <input type="file" name="image" class="form-control" accept="image/*">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Save</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="editProductModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="post" id="editProductForm" action="" enctype="multipart/form-data" class="modal-content">
            @csrf @method('PUT')
            <div class="modal-header"><h5 class="modal-title">Edit product</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="mb-3"><label class="form-label">Product name *</label><input type="text" name="product_name" id="edit-name" class="form-control" required></div>
                <div class="mb-3"><label class="form-label">Description</label><textarea name="description" id="edit-desc" class="form-control" rows="2"></textarea></div>
                <div class="row g-2">
                    <div class="col-6"><label class="form-label">Price *</label><input type="number" name="price" id="edit-price" class="form-control" step="0.01" min="0" required></div>
                    <div class="col-6"><label class="form-label">Cost</label><input type="number" name="cost" id="edit-cost" class="form-control" step="0.01" min="0"></div>
                </div>
                <div class="mb-3 mt-2"><label class="form-label">Status</label><select name="status" id="edit-status" class="form-select"><option value="active">Active</option><option value="inactive">Inactive</option></select></div>
                <div class="mb-3"><label class="form-label">Replace image</label><input type="file" name="image" class="form-control" accept="image/*"></div>
                <div class="form-check" id="edit-remove-wrap" style="display:none">
                    <input type="checkbox" name="remove_image" value="1" class="form-check-input" id="edit-remove-image">
                    <label class="form-check-label" for="edit-remove-image">Remove image</label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Update</button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('editProductModal')?.addEventListener('show.bs.modal', e => {
    const b = e.relatedTarget;
    document.getElementById('editProductForm').action = '{{ url('operator/products-for-sale') }}/' + b.dataset.id;
    document.getElementById('edit-name').value = b.dataset.name;
    document.getElementById('edit-desc').value = b.dataset.description || '';
    document.getElementById('edit-price').value = b.dataset.price;
    document.getElementById('edit-cost').value = b.dataset.cost || '';
    document.getElementById('edit-status').value = b.dataset.status;
    document.getElementById('edit-remove-wrap').style.display = b.dataset.hasImage === '1' ? 'block' : 'none';
});
</script>
@endsection
