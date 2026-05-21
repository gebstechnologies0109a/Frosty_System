@extends('layouts.app')
@section('title', 'Add product')
@section('content')
<h1 class="h4 mb-3">Add product</h1>
<form method="post" action="{{ route('admin.purchasing.products.store') }}" class="card p-4 col-lg-9">
    @csrf
    @include('admin.purchasing.products._form')
    <div class="d-flex gap-2">
        <button class="btn btn-primary">Save product</button>
        <a href="{{ route('admin.purchasing.products.index') }}" class="btn btn-outline-secondary">Cancel</a>
    </div>
</form>
@endsection
