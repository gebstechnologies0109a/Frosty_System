@extends('layouts.operator')
@section('header_title', 'Inventory')
@section('title', 'Supplies Inventory')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
        <h1 class="h4 mb-1 fw-bold">Supplies Inventory</h1>
        <p class="text-muted small mb-0"><i class="fa-solid fa-boxes-stacked me-1"></i> Raw materials — tap Adjust to update stock</p>
    </div>
    <a href="{{ route('operator.products-for-sale.index') }}" class="btn btn-outline-primary btn-sm"><i class="fa-solid fa-ice-cream me-1"></i>Store Menu</a>
</div>

<div class="card border-0 shadow-sm mb-4 frosty-chart-card">
    <div class="card-body">
        <form method="get" class="row g-2 align-items-end">
            <div class="col-sm-5">
                <label class="form-label small fw-semibold">Category</label>
                <select name="category" class="form-select form-select-sm">
                    <option value="">All categories</option>
                    @foreach ($categories as $val => $label)
                        <option value="{{ $val }}" @selected(($filters['category'] ?? '') === $val)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-sm-5">
                <label class="form-label small fw-semibold">Stock status</label>
                <select name="stock_status" class="form-select form-select-sm">
                    <option value="">All</option>
                    <option value="in_stock" @selected(($filters['stock_status'] ?? '') === 'in_stock')>In Stock</option>
                    <option value="low_stock" @selected(($filters['stock_status'] ?? '') === 'low_stock')>Low Stock</option>
                    <option value="out_of_stock" @selected(($filters['stock_status'] ?? '') === 'out_of_stock')>Out of Stock</option>
                </select>
            </div>
            <div class="col-auto d-flex gap-2">
                <button type="submit" class="btn btn-frosty btn-sm">Filter</button>
                <a href="{{ route('operator.supplies-inventory.index') }}" class="btn btn-outline-secondary btn-sm">Reset</a>
            </div>
        </form>
    </div>
</div>

@forelse ($grouped as $group)
    <div class="card border-0 shadow-sm mb-3 frosty-chart-card">
        <div class="card-header fw-semibold"><i class="fa-solid fa-layer-group me-1 text-frosty-primary"></i>{{ $group['label'] }}</div>
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0 align-middle">
                <thead>
                    <tr class="text-muted small">
                        <th>Product</th>
                        <th class="d-none d-sm-table-cell">Category</th>
                        <th class="text-end">Stock</th>
                        <th>Status</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                @foreach ($group['items'] as $row)
                    @php $status = $row->stockStatus(); @endphp
                    <tr>
                        <td class="fw-medium">{{ $row->product->name }}</td>
                        <td class="d-none d-sm-table-cell small">{{ $row->product->category->label() }}</td>
                        <td class="text-end fw-semibold">{{ number_format($row->stock) }}</td>
                        <td>
                            @if ($status === 'in_stock')
                                <span class="badge text-bg-success">In Stock</span>
                            @elseif ($status === 'low_stock')
                                <span class="badge text-bg-warning text-dark">Low</span>
                            @else
                                <span class="badge text-bg-danger">Out</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <button type="button" class="btn btn-sm btn-outline-primary"
                                data-bs-toggle="modal" data-bs-target="#adjustModal"
                                data-product-id="{{ $row->product_id }}"
                                data-product-name="{{ $row->product->name }}"
                                data-stock="{{ $row->stock }}"
                                data-min="{{ $row->minimum_stock ?? '' }}">
                                <i class="fa-solid fa-sliders"></i><span class="d-none d-sm-inline ms-1">Adjust</span>
                            </button>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
@empty
    <div class="alert alert-info"><i class="fa-solid fa-circle-info me-1"></i>No supply items match your filters.</div>
@endforelse

@include('operator.partials.inventory-adjust-modal')
@endsection
