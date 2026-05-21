<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#007bff">
    <title>@yield('title', 'Operator') — {{ config('app.name') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --frosty-blue: #007bff;
            --frosty-nav-h: 4.25rem;
            --frosty-bottom-nav-h: 4.5rem;
            --frosty-fab-size: 3.5rem;
        }
        body.operator-shell {
            min-height: 100vh;
            padding-bottom: calc(var(--frosty-bottom-nav-h) + env(safe-area-inset-bottom, 0px) + 1rem);
        }
        @media (min-width: 992px) {
            body.operator-shell { padding-bottom: 1.5rem; }
        }
        .operator-topbar {
            background: var(--frosty-blue);
            position: sticky;
            top: 0;
            z-index: 1040;
        }
        .operator-drawer .nav-link {
            border-radius: .5rem;
            padding: .65rem .85rem;
            color: inherit;
        }
        .operator-drawer .nav-link:hover,
        .operator-drawer .nav-link.active {
            background: rgba(0, 123, 255, .12);
            color: var(--frosty-blue);
        }
        .operator-drawer .nav-section {
            font-size: .7rem;
            font-weight: 700;
            letter-spacing: .06em;
            text-transform: uppercase;
            color: var(--bs-secondary-color);
            margin: 1rem 0 .35rem;
            padding-left: .85rem;
        }
        .operator-bottom-nav {
            position: fixed;
            left: 0;
            right: 0;
            bottom: 0;
            height: var(--frosty-bottom-nav-h);
            padding-bottom: env(safe-area-inset-bottom, 0px);
            background: var(--bs-body-bg);
            border-top: 1px solid var(--bs-border-color);
            z-index: 1030;
            display: flex;
        }
        @media (min-width: 992px) {
            .operator-bottom-nav { display: none; }
            .operator-desktop-sidebar {
                display: block !important;
                position: sticky;
                top: 4.5rem;
                height: calc(100vh - 4.5rem);
            }
            .operator-fab { display: none; }
        }
        .operator-bottom-nav a {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: .15rem;
            text-decoration: none;
            color: var(--bs-secondary-color);
            font-size: .65rem;
            font-weight: 600;
            padding-top: .35rem;
        }
        .operator-bottom-nav a i { font-size: 1.25rem; }
        .operator-bottom-nav a.active { color: var(--frosty-blue); }
        .operator-fab {
            position: fixed;
            right: 1rem;
            bottom: calc(var(--frosty-bottom-nav-h) + env(safe-area-inset-bottom, 0px) + .75rem);
            width: var(--frosty-fab-size);
            height: var(--frosty-fab-size);
            border-radius: 50%;
            background: var(--frosty-blue);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 14px rgba(0, 123, 255, .45);
            z-index: 1035;
            text-decoration: none;
            border: none;
        }
        .operator-fab span {
            position: absolute;
            width: 1px;
            height: 1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
        }
        .operator-fab i { font-size: 1.35rem; }
        .operator-quick-tile {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: .4rem;
            padding: 1rem .5rem;
            border-radius: 1rem;
            background: var(--bs-body-bg);
            border: 1px solid var(--bs-border-color);
            text-decoration: none;
            color: inherit;
            min-height: 5.5rem;
            transition: transform .15s, box-shadow .15s;
        }
        .operator-quick-tile:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(0, 0, 0, .08);
            color: inherit;
        }
        .operator-quick-tile i {
            font-size: 1.5rem;
            color: var(--frosty-blue);
        }
        .operator-stat-card {
            border-radius: 1rem;
            border: 1px solid var(--bs-border-color);
            padding: 1rem;
            height: 100%;
            text-decoration: none;
            color: inherit;
            display: block;
            transition: transform .15s, box-shadow .15s;
        }
        .operator-stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, .08);
            color: inherit;
        }
        .operator-glance-item {
            border-radius: .75rem;
            padding: .85rem;
            background: var(--bs-tertiary-bg);
        }
        #ptr-indicator {
            position: fixed;
            top: 3.5rem;
            left: 50%;
            transform: translateX(-50%) translateY(-120%);
            transition: transform .2s;
            z-index: 1050;
            background: var(--bs-body-bg);
            border-radius: 2rem;
            padding: .35rem 1rem;
            box-shadow: 0 2px 12px rgba(0,0,0,.12);
            font-size: .85rem;
        }
        #ptr-indicator.ptr-visible { transform: translateX(-50%) translateY(0); }
        .operator-desktop-sidebar { display: none; }
    </style>
    @stack('head')
</head>
@php
    $operator = auth()->user();
    $currentRoute = request()->route()?->getName();
    $hideFab = $currentRoute === 'operator.pos.index';
    $bottomNavActive = match ($currentRoute) {
        'operator.pos.index' => 'pos',
        'operator.supplies-inventory.index' => 'inventory',
        'operator.products-for-sale.index' => 'menu',
        'operator.dashboard' => 'dashboard',
        default => null,
    };
@endphp
<body class="operator-shell bg-body-tertiary">
<header class="operator-topbar text-white shadow-sm">
    <div class="container-fluid px-3 py-2 d-flex align-items-center gap-2">
        <button class="btn btn-sm btn-light text-primary d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#operatorDrawer" aria-label="Open menu">
            <i class="fa-solid fa-bars"></i>
        </button>
        <div class="flex-grow-1 min-w-0">
            <div class="fw-bold text-truncate small opacity-75">{{ config('app.name') }}</div>
            <div class="fw-semibold text-truncate">@yield('header_title', 'Operator')</div>
        </div>
        <button type="button" class="btn btn-sm btn-outline-light" id="themeToggle" aria-label="Toggle theme">
            <i class="fa-solid fa-moon" id="themeIcon"></i>
        </button>
        <span class="d-none d-sm-inline small text-white-50 text-truncate" style="max-width:6rem">{{ $operator?->name }}</span>
    </div>
</header>

<div class="offcanvas offcanvas-start operator-drawer" tabindex="-1" id="operatorDrawer">
    <div class="offcanvas-header border-bottom">
        <h5 class="offcanvas-title fw-bold"><i class="fa-solid fa-snowflake text-primary me-2"></i>Menu</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body p-2">
        <div class="nav-section">Main</div>
        <nav class="nav flex-column gap-1 mb-2">
            <a class="nav-link {{ $currentRoute === 'operator.dashboard' ? 'active' : '' }}" href="{{ route('operator.dashboard') }}"><i class="fa-solid fa-gauge-high me-2 w-20px"></i>Dashboard</a>
            <a class="nav-link {{ $currentRoute === 'operator.pos.index' ? 'active' : '' }}" href="{{ route('operator.pos.index') }}"><i class="fa-solid fa-cash-register me-2 w-20px"></i>Frosty POS</a>
            <a class="nav-link {{ $currentRoute === 'operator.supplies-inventory.index' ? 'active' : '' }}" href="{{ route('operator.supplies-inventory.index') }}"><i class="fa-solid fa-boxes-stacked me-2 w-20px"></i>Inventory</a>
            <a class="nav-link {{ $currentRoute === 'operator.products-for-sale.index' ? 'active' : '' }}" href="{{ route('operator.products-for-sale.index') }}"><i class="fa-solid fa-ice-cream me-2 w-20px"></i>Store Menu</a>
        </nav>
        <div class="nav-section">Orders</div>
        <nav class="nav flex-column gap-1 mb-2">
            <a class="nav-link {{ $currentRoute === 'operator.orders.create' ? 'active' : '' }}" href="{{ route('operator.orders.create') }}"><i class="fa-solid fa-cart-plus me-2 w-20px"></i>New order</a>
            <a class="nav-link {{ $currentRoute === 'operator.orders.index' ? 'active' : '' }}" href="{{ route('operator.orders.index') }}"><i class="fa-solid fa-list me-2 w-20px"></i>Order history</a>
        </nav>
        <div class="nav-section">Network</div>
        <nav class="nav flex-column gap-1 mb-2">
            <a class="nav-link {{ $currentRoute === 'operator.analytics' ? 'active' : '' }}" href="{{ route('operator.analytics') }}"><i class="fa-solid fa-chart-line me-2 w-20px"></i>Analytics</a>
            <a class="nav-link {{ $currentRoute === 'operator.genealogy' ? 'active' : '' }}" href="{{ route('operator.genealogy') }}"><i class="fa-solid fa-sitemap me-2 w-20px"></i>Genealogy</a>
            <a class="nav-link {{ $currentRoute === 'operator.referrals.create' ? 'active' : '' }}" href="{{ route('operator.referrals.create') }}"><i class="fa-solid fa-user-plus me-2 w-20px"></i>Add operator</a>
        </nav>
        <div class="nav-section">Wallet</div>
        <nav class="nav flex-column gap-1 mb-2">
            <a class="nav-link {{ $currentRoute === 'operator.wallet' ? 'active' : '' }}" href="{{ route('operator.wallet') }}"><i class="fa-solid fa-wallet me-2 w-20px"></i>Wallet</a>
            <a class="nav-link {{ $currentRoute === 'operator.rebates' ? 'active' : '' }}" href="{{ route('operator.rebates') }}"><i class="fa-solid fa-coins me-2 w-20px"></i>Rebates</a>
        </nav>
        <div class="nav-section">Account</div>
        <form method="post" action="{{ route('logout') }}" class="px-2">@csrf
            <button type="submit" class="btn btn-outline-danger w-100"><i class="fa-solid fa-right-from-bracket me-2"></i>Logout</button>
        </form>
    </div>
</div>

<div class="container-fluid py-3">
    <div class="row g-3">
        <aside class="col-lg-3 operator-desktop-sidebar">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-2 operator-drawer">
                    <div class="nav-section">Main</div>
                    <nav class="nav flex-column gap-1">
                        <a class="nav-link {{ $currentRoute === 'operator.dashboard' ? 'active' : '' }}" href="{{ route('operator.dashboard') }}"><i class="fa-solid fa-gauge-high me-2"></i>Dashboard</a>
                        <a class="nav-link {{ $currentRoute === 'operator.pos.index' ? 'active' : '' }}" href="{{ route('operator.pos.index') }}"><i class="fa-solid fa-cash-register me-2"></i>Frosty POS</a>
                        <a class="nav-link {{ $currentRoute === 'operator.supplies-inventory.index' ? 'active' : '' }}" href="{{ route('operator.supplies-inventory.index') }}"><i class="fa-solid fa-boxes-stacked me-2"></i>Inventory</a>
                        <a class="nav-link {{ $currentRoute === 'operator.products-for-sale.index' ? 'active' : '' }}" href="{{ route('operator.products-for-sale.index') }}"><i class="fa-solid fa-ice-cream me-2"></i>Store Menu</a>
                    </nav>
                    <div class="nav-section">More</div>
                    <nav class="nav flex-column gap-1">
                        <a class="nav-link" href="{{ route('operator.orders.create') }}"><i class="fa-solid fa-cart-plus me-2"></i>New order</a>
                        <a class="nav-link" href="{{ route('operator.analytics') }}"><i class="fa-solid fa-chart-line me-2"></i>Analytics</a>
                        <a class="nav-link" href="{{ route('operator.wallet') }}"><i class="fa-solid fa-wallet me-2"></i>Wallet</a>
                    </nav>
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
<a href="{{ route('operator.pos.index') }}" class="operator-fab" title="Frosty POS" aria-label="Frosty POS">
    <i class="fa-solid fa-ice-cream"></i>
    <span>Frosty POS</span>
</a>
@endif

<nav class="operator-bottom-nav d-lg-none" aria-label="Primary">
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
<script>
(function () {
    const html = document.documentElement;
    const toggle = document.getElementById('themeToggle');
    const icon = document.getElementById('themeIcon');
    const stored = localStorage.getItem('frosty-theme');
    if (stored) html.setAttribute('data-bs-theme', stored);
    function syncIcon() {
        const dark = html.getAttribute('data-bs-theme') === 'dark';
        icon.className = dark ? 'fa-solid fa-sun' : 'fa-solid fa-moon';
    }
    syncIcon();
    toggle?.addEventListener('click', function () {
        const next = html.getAttribute('data-bs-theme') === 'dark' ? 'light' : 'dark';
        html.setAttribute('data-bs-theme', next);
        localStorage.setItem('frosty-theme', next);
        syncIcon();
    });

    @if ($currentRoute === 'operator.dashboard')
    const ptr = document.getElementById('ptr-scroll');
    const indicator = document.getElementById('ptr-indicator');
    let startY = 0, pulling = false;
    ptr?.addEventListener('touchstart', function (e) {
        if (window.scrollY <= 0) { startY = e.touches[0].clientY; pulling = true; }
    }, { passive: true });
    ptr?.addEventListener('touchmove', function (e) {
        if (!pulling) return;
        const dy = e.touches[0].clientY - startY;
        if (dy > 50) indicator?.classList.add('ptr-visible');
        else indicator?.classList.remove('ptr-visible');
    }, { passive: true });
    ptr?.addEventListener('touchend', function () {
        if (indicator?.classList.contains('ptr-visible')) window.location.reload();
        indicator?.classList.remove('ptr-visible');
        pulling = false;
    });
    @endif
})();
</script>
@stack('scripts')
</body>
</html>
