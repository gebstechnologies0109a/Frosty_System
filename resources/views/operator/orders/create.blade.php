@extends('layouts.operator')
@section('header_title', 'New order')
@section('title', 'New Order')
@section('content')
<h1 class="h4 mb-3">Operator order</h1>
@include('partials.order-form', [
    'action' => route('operator.orders.store'),
    'showDistributor' => true,
    'distributors' => $distributors,
    'showPaymentProof' => true,
    'useProductSearch' => true,
    'productSearchUrl' => route('operator.products.search'),
])
@endsection
