@extends('layouts.app')
@section('title', 'Products')
@section('content')
@include('admin.partials.page-header', [
    'title' => 'Products',
    'actions' => '<a href="'.route('admin.products.create').'" class="btn btn-primary">Add Product</a>',
])
<form method="get" class="card border-0 shadow-sm mb-3"><div class="card-body row g-2 align-items-end">
    <div class="col-md-4"><label class="form-label small">Search</label><input name="q" class="form-control form-control-sm" value="{{ request('q') }}"></div>
    <div class="col-md-3"><label class="form-label small">Category</label><select name="category" class="form-select form-select-sm"><option value="">All</option>@foreach ($categories as $c)<option value="{{ $c->value }}" @selected(request('category') === $c->value)>{{ $c->label() }}</option>@endforeach</select></div>
    <div class="col-md-2"><label class="form-label small">Status</label><select name="status" class="form-select form-select-sm"><option value="">All</option><option value="active" @selected(request('status') === 'active')>Active</option><option value="inactive" @selected(request('status') === 'inactive')>Inactive</option></select></div>
    <div class="col-auto"><button class="btn btn-primary btn-sm">Filter</button> <a href="{{ route('admin.products.index') }}" class="btn btn-outline-secondary btn-sm">Reset</a></div>
</div></form>
<div class="card border-0 shadow-sm">
    <table class="table table-hover mb-0">
        <thead class="table-light"><tr><th>Name</th><th>Category</th><th>Stock</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
        <tbody>
        @forelse ($products as $p)
            <tr>
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
            <tr><td colspan="5" class="text-center text-muted py-3">No products.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@include('partials.list-pagination', ['paginator' => $products])
@endsection
