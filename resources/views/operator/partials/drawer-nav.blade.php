<div class="nav-section">Main</div>
<nav class="nav flex-column gap-1 mb-2">
    <a class="nav-link {{ $currentRoute === 'operator.dashboard' ? 'active' : '' }}" href="{{ route('operator.dashboard') }}"><i class="fa-solid fa-gauge-high me-2"></i>Dashboard</a>
    <a class="nav-link {{ $currentRoute === 'operator.pos.index' ? 'active' : '' }}" href="{{ route('operator.pos.index') }}"><i class="fa-solid fa-cash-register me-2"></i>Frosty POS</a>
    <a class="nav-link {{ $currentRoute === 'operator.supplies-inventory.index' ? 'active' : '' }}" href="{{ route('operator.supplies-inventory.index') }}"><i class="fa-solid fa-boxes-stacked me-2"></i>Inventory</a>
    <a class="nav-link {{ $currentRoute === 'operator.products-for-sale.index' ? 'active' : '' }}" href="{{ route('operator.products-for-sale.index') }}"><i class="fa-solid fa-ice-cream me-2"></i>Store Menu</a>
</nav>
<div class="nav-section">Orders</div>
<nav class="nav flex-column gap-1 mb-2">
    <a class="nav-link {{ $currentRoute === 'operator.orders.create' ? 'active' : '' }}" href="{{ route('operator.orders.create') }}"><i class="fa-solid fa-cart-plus me-2"></i>New order</a>
    <a class="nav-link {{ $currentRoute === 'operator.orders.index' ? 'active' : '' }}" href="{{ route('operator.orders.index') }}"><i class="fa-solid fa-list me-2"></i>Order history</a>
</nav>
<div class="nav-section">Network</div>
<nav class="nav flex-column gap-1 mb-2">
    <a class="nav-link {{ $currentRoute === 'operator.analytics' ? 'active' : '' }}" href="{{ route('operator.analytics') }}"><i class="fa-solid fa-chart-line me-2"></i>Analytics</a>
    <a class="nav-link {{ $currentRoute === 'operator.genealogy' ? 'active' : '' }}" href="{{ route('operator.genealogy') }}"><i class="fa-solid fa-sitemap me-2"></i>Genealogy</a>
    <a class="nav-link {{ $currentRoute === 'operator.referrals.create' ? 'active' : '' }}" href="{{ route('operator.referrals.create') }}"><i class="fa-solid fa-user-plus me-2"></i>Add operator</a>
</nav>
<div class="nav-section">Wallet</div>
<nav class="nav flex-column gap-1 mb-2">
    <a class="nav-link {{ $currentRoute === 'operator.wallet' ? 'active' : '' }}" href="{{ route('operator.wallet') }}"><i class="fa-solid fa-wallet me-2"></i>Wallet</a>
    <a class="nav-link {{ $currentRoute === 'operator.rebates' ? 'active' : '' }}" href="{{ route('operator.rebates') }}"><i class="fa-solid fa-coins me-2"></i>Rebates</a>
</nav>
<div class="nav-section">Account</div>
<form method="post" action="{{ route('logout') }}" class="px-2">@csrf
    <button type="submit" class="btn btn-outline-danger w-100"><i class="fa-solid fa-right-from-bracket me-2"></i>Logout</button>
</form>
