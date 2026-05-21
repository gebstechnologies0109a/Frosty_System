@extends('layouts.app')
@section('title', 'Store menu')
@section('content')
@include('admin.partials.page-header', ['title' => 'Store menu — '.$operator->name])
<div class="card border-0 shadow-sm">
    <table class="table table-sm mb-0">
        <thead class="table-light"><tr><th>Product</th><th class="text-end">Price</th><th>Status</th></tr></thead>
        <tbody>
        @forelse ($products as $p)
            <tr>
                <td>{{ $p->product_name }}</td>
                <td class="text-end">₱{{ number_format($p->price, 2) }}</td>
                <td><span class="badge text-bg-{{ $p->isActive() ? 'success' : 'secondary' }}">{{ $p->isActive() ? 'Active' : 'Inactive' }}</span></td>
            </tr>
        @empty
            <tr><td colspan="3" class="text-muted text-center py-3">No store menu items.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@include('partials.list-pagination', ['paginator' => $products])
@endsection
