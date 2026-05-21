@extends('layouts.app')
@section('title', 'Distributor Inventory')

@section('content')
<div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1">Inventory — {{ $distributor->name }}</h1>
        <span class="badge text-bg-primary">
            Regional pricing: {{ $pricingRegion->label() }} ({{ $priceRegion->value }})
        </span>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('distributor.inventory.adjust') }}" class="btn btn-primary btn-sm">Adjust stock</a>
        <a href="{{ route('distributor.dashboard') }}" class="btn btn-outline-primary btn-sm">← Dashboard</a>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold">Catalog ({{ $priceRegion->value }} prices)</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Product</th>
                                <th>Category</th>
                                <th class="text-end">Price</th>
                                <th class="text-end">Main stock</th>
                                <th class="text-end">Points</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($products as $product)
                                <tr>
                                    <td class="fw-semibold">{{ $product->name }}</td>
                                    <td>{{ $product->category->label() }}</td>
                                    <td class="text-end">₱{{ number_format($product->priceForRegion($priceRegion), 2) }}</td>
                                    <td class="text-end">{{ $product->stockLevel() }}</td>
                                    <td class="text-end">{{ $product->points }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-muted p-3">No active products for this region.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold">Operator supplies stock</div>
            <div class="card-body p-0">
                <div class="table-responsive" style="max-height: 480px;">
                    <table class="table table-sm mb-0">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th>Operator</th>
                                <th>Product</th>
                                <th class="text-end">Qty</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($operatorStock as $row)
                                <tr>
                                    <td>{{ $row->operator?->name }}</td>
                                    <td>{{ $row->product?->name }}</td>
                                    <td class="text-end">{{ $row->stock }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="text-muted p-3">No operator inventory rows.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
