<ul class="nav nav-pills mb-3">
    <li class="nav-item"><a class="nav-link {{ request()->routeIs('admin.finance.wallets') ? 'active' : '' }}" href="{{ route('admin.finance.wallets') }}">Wallets</a></li>
    <li class="nav-item"><a class="nav-link {{ request()->routeIs('admin.finance.rebates') ? 'active' : '' }}" href="{{ route('admin.finance.rebates') }}">Rebates</a></li>
    <li class="nav-item"><a class="nav-link {{ request()->routeIs('admin.finance.overrides') ? 'active' : '' }}" href="{{ route('admin.finance.overrides') }}">Overrides</a></li>
    <li class="nav-item"><a class="nav-link {{ request()->routeIs('admin.finance.withdrawals') ? 'active' : '' }}" href="{{ route('admin.finance.withdrawals') }}">Withdrawals</a></li>
    <li class="nav-item"><a class="nav-link {{ request()->routeIs('admin.finance.reports') ? 'active' : '' }}" href="{{ route('admin.finance.reports') }}">Reports</a></li>
</ul>
