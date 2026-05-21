@extends('layouts.app')
@section('title', 'Add Operator')
@section('content')
@include('admin.partials.page-header', ['title' => 'Add Operator'])
<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="post" action="{{ route('admin.operators.store') }}">
            @csrf
            @include('admin.operators._form', ['distributors' => $distributors])
            <div class="mt-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary">Create operator</button>
                <a href="{{ route('admin.operators.index') }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
