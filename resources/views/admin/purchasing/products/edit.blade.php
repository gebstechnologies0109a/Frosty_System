@extends('layouts.app')
@section('title', 'Edit product')
@section('content')
<h1 class="h4 mb-3">Edit product</h1>
<form method="post" action="{{ route('admin.purchasing.products.update', $product) }}" class="card p-4 col-lg-9">
    @csrf
    @method('PUT')
    @include('admin.purchasing.products._form')
    <div class="d-flex gap-2">
        <button class="btn btn-primary">Update product</button>
        <a href="{{ route('admin.purchasing.products.index') }}" class="btn btn-outline-secondary">Cancel</a>
    </div>
</form>
@endsection
