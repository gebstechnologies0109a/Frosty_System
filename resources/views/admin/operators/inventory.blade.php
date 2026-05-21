@extends('layouts.app')
@section('title', 'Operator inventory')
@section('content')
@include('admin.partials.page-header', ['title' => 'Inventory — '.$operator->name, 'actions' => '<a href="'.route('admin.operators.show', $operator).'" class="btn btn-outline-secondary btn-sm">Profile</a>'])
<div class="card border-0 shadow-sm">
    <table class="table table-sm mb-0">
        <thead class="table-light"><tr><th>Product</th><th class="text-end">Stock</th><th>Min</th></tr></thead>
        <tbody>
        @forelse ($items as $row)
            <tr>
                <td>{{ $row->product->name }}</td>
                <td class="text-end fw-semibold">{{ $row->stock }}</td>
                <td>{{ $row->minimum_stock ?? '—' }}</td>
            </tr>
        @empty
            <tr><td colspan="3" class="text-muted text-center py-3">No inventory records.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@include('partials.list-pagination', ['paginator' => $items])
@endsection
