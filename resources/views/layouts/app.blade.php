<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', config('app.name'))</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    @stack('head')
</head>
<body class="bg-light">
@php $user = auth()->user(); $role = $user?->role; @endphp
<nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
    <div class="container">
        <a class="navbar-brand fw-bold" href="{{ route('home') }}">{{ config('app.name') }}</a>
        @auth
        <div class="navbar-nav ms-auto align-items-lg-center gap-lg-2">
            @if ($role?->isAdmin())
                <a class="nav-link" href="{{ route('admin.dashboard') }}">Admin</a>
                @if ($role === \App\Enums\UserRole::PurchasingAdmin || $role === \App\Enums\UserRole::SuperAdmin)
                    <a class="nav-link" href="{{ route('admin.products.index') }}">Products</a>
                @endif
                @if ($role === \App\Enums\UserRole::PurchasingAdmin)
                    <a class="nav-link" href="{{ route('admin.purchasing.analytics') }}">Analytics</a>
                    <a class="nav-link" href="{{ route('admin.purchasing.stock-movements.index') }}">Stock Logs</a>
                @endif
                @if ($role === \App\Enums\UserRole::SuperAdmin)
                    <a class="nav-link" href="{{ route('admin.users.index') }}">Users</a>
                    <a class="nav-link" href="{{ route('admin.page-builder.index') }}">Page Builder</a>
                    <a class="nav-link" href="{{ route('admin.operators.index') }}">Operators</a>
                    <a class="nav-link" href="{{ route('admin.distributors.index') }}">Distributors</a>
                    <a class="nav-link" href="{{ route('admin.orders.pending') }}">Pending Orders</a>
                    <a class="nav-link" href="{{ route('admin.withdrawals.pending') }}">Withdrawals</a>
                    <a class="nav-link" href="{{ route('admin.pos.logs') }}">POS Logs</a>
                    <a class="nav-link" href="{{ route('admin.pos.closings') }}">POS Closings</a>
                @endif
                @if ($role === \App\Enums\UserRole::SuperAdmin || $role === \App\Enums\UserRole::PurchasingAdmin)
                    <a class="nav-link" href="{{ route('admin.orders.index') }}">Orders</a>
                @endif
                @if (in_array($role, [\App\Enums\UserRole::SuperAdmin, \App\Enums\UserRole::FinanceAdmin, \App\Enums\UserRole::PurchasingAdmin], true))
                    <a class="nav-link" href="{{ route('admin.orders.analytics') }}">Order Analytics</a>
                @endif
                @if ($role === \App\Enums\UserRole::SuperAdmin || $role === \App\Enums\UserRole::FinanceAdmin)
                    <a class="nav-link" href="{{ route('admin.finance.reports') }}">Finance</a>
                @endif
                @if ($role === \App\Enums\UserRole::SuperAdmin || $role === \App\Enums\UserRole::ItAdmin)
                    <a class="nav-link" href="{{ route('admin.settings.index') }}">Settings</a>
                @endif
            @endif
            @if ($role === \App\Enums\UserRole::Distributor)
                <a class="nav-link" href="{{ route('distributor.dashboard') }}">Dashboard</a>
                <a class="nav-link" href="{{ route('distributor.analytics') }}">Analytics</a>
                <a class="nav-link" href="{{ route('distributor.orders.index') }}">Orders</a>
            @endif
            @if ($role === \App\Enums\UserRole::Operator)
                <a class="nav-link" href="{{ route('operator.dashboard') }}">Dashboard</a>
                <a class="nav-link" href="{{ route('operator.analytics') }}">Analytics</a>
                <a class="nav-link" href="{{ route('operator.pos.index') }}">Frosty POS</a>
                <a class="nav-link" href="{{ route('operator.supplies-inventory.index') }}">Supplies</a>
                <a class="nav-link" href="{{ route('operator.products-for-sale.index') }}">Store Menu</a>
                <a class="nav-link" href="{{ route('operator.orders.create') }}">Order</a>
                <a class="nav-link" href="{{ route('operator.genealogy') }}">Genealogy</a>
                <a class="nav-link" href="{{ route('operator.wallet') }}">Wallet</a>
            @endif
            <span class="nav-link text-white-50 small">{{ $user->name }}</span>
            <form method="post" action="{{ route('logout') }}" class="d-inline">@csrf<button class="btn btn-sm btn-outline-light">Logout</button></form>
        </div>
        @endauth
    </div>
</nav>
<main class="container pb-5">
    @if (session(\App\Services\AdminImpersonationService::SESSION_IMPERSONATOR))
        <div class="alert alert-warning d-flex justify-content-between align-items-center">
            <span>You are impersonating another user.</span>
            <form method="post" action="{{ route('admin.users.stop-impersonate') }}">@csrf<button class="btn btn-sm btn-dark">Stop impersonating</button></form>
        </div>
    @endif
    @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if ($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif
    @if (! empty($pageBuilderOverlay))
        <div class="page-builder-overlay mb-4">{!! $pageBuilderOverlay !!}</div>
    @endif
    @yield('content')
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@stack('scripts')
</body>
</html>
