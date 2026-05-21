@extends('layouts.app')
@section('title', 'Operator Products for Sale')
@section('content')
<h1 class="h3 mb-4">Operator Products for Sale</h1>

<form method="get" class="card shadow-sm mb-4">
    <div class="card-body row g-2 align-items-end">
        <div class="col-md-4">
            <label class="form-label small">Operator</label>
            <select name="operator_id" class="form-select form-select-sm">
                <option value="">All</option>
                @foreach ($operators as $op)
                    <option value="{{ $op->id }}" @selected(($filters['operator_id'] ?? '') == $op->id)>{{ $op->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-auto"><button class="btn btn-primary btn-sm">Filter</button></div>
    </div>
</form>

<div class="table-responsive card shadow-sm">
    <table class="table table-sm mb-0">
        <thead class="table-light">
            <tr><th>Operator</th><th>Product</th><th class="text-end">Price</th><th class="text-end">Cost</th><th>Status</th><th></th></tr>
        </thead>
        <tbody>
        @foreach ($products as $p)
            <tr>
                <td>{{ $p->operator?->name }}</td>
                <td>{{ $p->product_name }} @if($p->is_system_default)<span class="badge text-bg-info">Default</span>@endif</td>
                <td class="text-end">₱{{ number_format($p->price, 2) }}</td>
                <td class="text-end">{{ $p->cost !== null ? '₱'.number_format($p->cost, 2) : '—' }}</td>
                <td>{{ $p->status }}</td>
                <td>
                    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editModal"
                        data-id="{{ $p->id }}"
                        data-name="{{ $p->product_name }}"
                        data-desc="{{ $p->description }}"
                        data-price="{{ $p->price }}"
                        data-cost="{{ $p->cost }}"
                        data-status="{{ $p->status }}">Edit</button>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@include('partials.list-pagination', ['paginator' => $products])

<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="post" id="adminEditForm" class="modal-content">
            @csrf @method('PUT')
            <div class="modal-header"><h5 class="modal-title">Edit product</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="mb-2"><label class="form-label">Name</label><input name="product_name" id="a-name" class="form-control" required></div>
                <div class="mb-2"><label class="form-label">Description</label><textarea name="description" id="a-desc" class="form-control" rows="2"></textarea></div>
                <div class="row g-2">
                    <div class="col-6"><label class="form-label">Price</label><input name="price" id="a-price" type="number" step="0.01" class="form-control" required></div>
                    <div class="col-6"><label class="form-label">Cost</label><input name="cost" id="a-cost" type="number" step="0.01" class="form-control"></div>
                </div>
                <div class="mt-2"><label class="form-label">Status</label><select name="status" id="a-status" class="form-select"><option value="active">active</option><option value="inactive">inactive</option></select></div>
            </div>
            <div class="modal-footer"><button type="submit" class="btn btn-primary">Save</button></div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('editModal')?.addEventListener('show.bs.modal', e => {
    const b = e.relatedTarget;
    document.getElementById('adminEditForm').action = '{{ url('admin/operator-products') }}/' + b.dataset.id;
    document.getElementById('a-name').value = b.dataset.name;
    document.getElementById('a-desc').value = b.dataset.desc || '';
    document.getElementById('a-price').value = b.dataset.price;
    document.getElementById('a-cost').value = b.dataset.cost || '';
    document.getElementById('a-status').value = b.dataset.status;
});
</script>
@endsection
