@extends('layouts.app')
@section('title', 'Order from Main')
@section('content')
<h1 class="h4 mb-3">Order from Main</h1>
@include('partials.order-form', ['action' => route('distributor.orders.store'), 'products' => $products, 'showDistributor' => false])
@endsection
