@extends('layouts.app')
@section('title', $product->name)
@section('content')
@include('admin.partials.page-header', [
    'title' => $product->name,
    'actions' => '<a href="'.route('admin.products.edit', $product).'" class="btn btn-outline-secondary">Edit</a>',
])
<div class="row g-3 mb-3">
    <div class="col-md-3"><div class="card p-3"><div class="small text-muted">Category</div><div class="fw-bold">{{ $product->category->label() }}</div></div></div>
    <div class="col-md-3"><div class="card p-3"><div class="small text-muted">Points</div><div class="fw-bold">{{ $product->points }}</div></div></div>
    <div class="col-md-3"><div class="card p-3"><div class="small text-muted">Stock</div><div class="fw-bold">{{ $product->stockLevel() }}</div></div></div>
    <div class="col-md-3"><div class="card p-3"><div class="small text-muted">Status</div><div class="fw-bold">{{ $product->status }}</div></div></div>
</div>
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header fw-semibold">Regional prices</div>
    <ul class="list-group list-group-flush">
        @foreach ($product->prices as $price)
            <li class="list-group-item d-flex justify-content-between"><span>{{ $price->region->label() }}</span><strong>₱{{ number_format($price->price, 2) }}</strong></li>
        @endforeach
    </ul>
</div>
<p>
    <a href="{{ route('admin.products.stock-logs', $product) }}" class="btn btn-outline-primary btn-sm">Stock logs</a>
    <a href="{{ route('admin.products.price-history', $product) }}" class="btn btn-outline-primary btn-sm">Price history</a>
</p>
<a href="{{ route('admin.products.index') }}" class="btn btn-link">Back</a>
@endsection
