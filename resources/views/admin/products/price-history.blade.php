@extends('layouts.app')
@section('title', 'Price history')
@section('content')
@include('admin.partials.page-header', ['title' => 'Price history — '.$product->name])
<div class="card border-0 shadow-sm">
    <table class="table table-sm mb-0">
        <thead class="table-light"><tr><th>Region</th><th class="text-end">Price</th><th>Updated</th></tr></thead>
        <tbody>
        @foreach ($prices as $price)
            <tr>
                <td>{{ $price->region->label() }}</td>
                <td class="text-end">₱{{ number_format($price->price, 2) }}</td>
                <td>{{ $price->updated_at?->format('M j, Y H:i') }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endsection
