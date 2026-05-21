@php
    $ui = config('frosty-ui');
    $operator = auth()->user();
    $currentRoute = request()->route()?->getName();
    $hideFab = $currentRoute === 'operator.pos.index';
    $ptrEnabled = in_array($currentRoute, $ui['pull_to_refresh_routes'] ?? [], true);
    $bottomNavActive = match ($currentRoute) {
        'operator.pos.index' => 'pos',
        'operator.supplies-inventory.index' => 'inventory',
        'operator.products-for-sale.index' => 'menu',
        'operator.dashboard' => 'dashboard',
        default => null,
    };
@endphp
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="{{ $ui['colors']['primary'] }}">
    <title>@yield('title', 'Operator') — {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="{{ $ui['font_url'] }}" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <style>
        @foreach ($ui['colors'] as $key => $value)
        --frosty-{{ str_replace('_', '-', $key) }}: {{ $value }};
        @endforeach
        --frosty-font: '{{ $ui['font_family'] }}', system-ui, sans-serif;
        --frosty-bottom-nav-h: {{ $ui['layout']['bottom_nav_height'] }};
        --frosty-topbar-h: {{ $ui['layout']['topbar_height'] }};
        --frosty-fab-size: {{ $ui['layout']['fab_size'] }};
    </style>
    @vite(['resources/css/frosty-operator.css', 'resources/js/frosty-operator.js'])
    @stack('head')
</head>
<body class="operator-shell" data-ptr="{{ $ptrEnabled ? '1' : '0' }}">
<script>window.FrostyUI = @json($ui);</script>

<header class="operator-topbar text-white shadow-sm">
    <div class="container-fluid px-3 py-2 d-flex align-items-center gap-2">
        <button class="btn btn-sm btn-light text-frosty-primary d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#operatorDrawer" aria-label="Open menu">
            <i class="fa-solid fa-bars"></i>
        </button>
        <div class="flex-grow-1 min-w-0">
            <div class="fw-bold text-truncate small opacity-75">{{ config('app.name') }}</div>
            <div class="fw-semibold text-truncate">@yield('header_title', 'Operator')</div>
        </div>
        <button type="button" class="btn btn-sm btn-outline-light" id="themeToggle" aria-label="Toggle light/dark mode">
            <i class="fa-solid fa-moon" id="themeIcon"></i>
        </button>
        <span class="d-none d-sm-inline small text-white-50 text-truncate" style="max-width:6rem">{{ $operator?->name }}</span>
    </div>
</header>

<div class="offcanvas offcanvas-start operator-drawer" tabindex="-1" id="operatorDrawer">
    <div class="offcanvas-header border-bottom">
        <h5 class="offcanvas-title fw-bold"><i class="fa-solid fa-snowflake text-frosty-primary me-2"></i>Menu</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body p-2">
        @include('operator.partials.drawer-nav', ['currentRoute' => $currentRoute])
    </div>
</div>

<div class="container-fluid py-3">
    <div class="row g-3">
        <aside class="col-lg-3 operator-desktop-sidebar">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-2 operator-drawer">
                    @include('operator.partials.drawer-nav', ['currentRoute' => $currentRoute])
                </div>
            </div>
        </aside>
        <div class="col-lg-9">
            <div id="ptr-indicator"><i class="fa-solid fa-rotate me-1"></i> Pull to refresh</div>
            <div id="ptr-scroll">
                @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
                @if ($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif
                @yield('content')
            </div>
        </div>
    </div>
</div>

@if (! $hideFab)
<a href="{{ route('operator.pos.index') }}" class="operator-fab" title="Frosty POS" aria-label="Open Frosty POS">
    <i class="fa-solid fa-cash-register"></i>
    <span>Frosty POS</span>
</a>
@endif

<nav class="operator-bottom-nav d-lg-none" aria-label="Primary navigation">
    <a href="{{ route('operator.pos.index') }}" class="{{ $bottomNavActive === 'pos' ? 'active' : '' }}">
        <i class="fa-solid fa-cash-register"></i><span>POS</span>
    </a>
    <a href="{{ route('operator.supplies-inventory.index') }}" class="{{ $bottomNavActive === 'inventory' ? 'active' : '' }}">
        <i class="fa-solid fa-boxes-stacked"></i><span>Inventory</span>
    </a>
    <a href="{{ route('operator.products-for-sale.index') }}" class="{{ $bottomNavActive === 'menu' ? 'active' : '' }}">
        <i class="fa-solid fa-ice-cream"></i><span>Store Menu</span>
    </a>
    <a href="{{ route('operator.dashboard') }}" class="{{ $bottomNavActive === 'dashboard' ? 'active' : '' }}">
        <i class="fa-solid fa-gauge-high"></i><span>Dashboard</span>
    </a>
</nav>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@stack('scripts')
</body>
</html>
