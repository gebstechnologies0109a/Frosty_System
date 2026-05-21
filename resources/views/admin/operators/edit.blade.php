@extends('layouts.app')
@section('title', 'Edit Operator')
@section('content')
@include('admin.partials.page-header', ['title' => 'Edit '.$operator->name])
<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="post" action="{{ route('admin.operators.update', $operator) }}">
            @csrf @method('PUT')
            @include('admin.operators._form', compact('operator', 'distributors', 'statuses', 'regions'))
            <div class="mt-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary">Save</button>
                <a href="{{ route('admin.operators.show', $operator) }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
