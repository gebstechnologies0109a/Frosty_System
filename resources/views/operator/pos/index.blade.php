@extends('layouts.operator')
@section('title', 'Frosty POS')
@section('header_title', 'Frosty POS')
@push('head')
<style>
.pos-shell { min-height: calc(100vh - 100px); }
.pos-search { font-size: 1.1rem; padding: .75rem 1rem; }
.pos-product-card {
    min-height: 110px; border: 2px solid #dee2e6; border-radius: .75rem;
    padding: .85rem; background: #fff; width: 100%; text-align: left;
}
.pos-product-card:active { transform: scale(.98); }
.pos-qty-btn { min-width: 48px; min-height: 48px; font-size: 1.3rem; }
.pos-checkout-bar {
    position: fixed; left: 0; right: 0; bottom: 0;
    padding: .85rem .85rem calc(.85rem + env(safe-area-inset-bottom, 0px));
    background: var(--bs-body-bg); border-top: 3px solid var(--frosty-success, #198754);
    z-index: 1045; box-shadow: 0 -4px 20px rgba(0,0,0,.12);
}
@media (min-width: 992px) {
    .pos-checkout-bar { display: none !important; }
}
body.operator-shell--pos { padding-bottom: calc(5.25rem + env(safe-area-inset-bottom, 0px)); }
.pos-pnl { font-size: .9rem; }
</style>
@endpush
@section('content')
<div class="pos-shell">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <h1 class="h4 fw-bold mb-0">Frosty POS</h1>
        <a href="{{ route('operator.dashboard') }}" class="btn btn-outline-secondary d-none d-md-inline-flex">Exit</a>
    </div>

    @if ($dayLocked)
        <div class="alert alert-warning mb-3">
            <strong>Sales are locked for today.</strong>
            Contact your administrator if you need to record more sales.
        </div>
    @endif

    <p class="text-muted small">Finished goods only — summary totals only (no per-transaction history)</p>

    <div class="row g-4">
        <div class="col-lg-8">
            <input type="search" id="pos-search" class="form-control pos-search mb-3" placeholder="Search finished goods…" autocomplete="off">
            <div class="row g-2" id="pos-grid">
                @forelse ($products as $p)
                    <div class="col-6 col-md-4 pos-product-wrap" data-name="{{ strtolower($p['name']) }}">
                        <button type="button" class="pos-product-card pos-add-btn w-100"
                            data-id="{{ $p['operator_product_id'] }}"
                            data-name="{{ $p['name'] }}"
                            data-price="{{ $p['price'] }}">
                            @if ($p['image_url'])
                                <img src="{{ $p['image_url'] }}" alt="" class="rounded mb-2 w-100" style="height:64px;object-fit:cover">
                            @endif
                            <div class="fw-semibold">{{ $p['name'] }}</div>
                            <div class="fs-5 fw-bold text-success">₱{{ number_format($p['price'], 2) }}</div>
                            <span class="badge text-bg-success">Active</span>
                        </button>
                    </div>
                @empty
                    <div class="col-12 alert alert-warning">
                        Add finished goods in <a href="{{ route('operator.products-for-sale.index') }}">Products for Sale</a>.
                    </div>
                @endforelse
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-primary text-white fw-semibold fs-5">Cart</div>
                <div id="pos-cart" class="p-2" style="min-height:160px;max-height:40vh;overflow-y:auto">
                    <div class="text-center text-muted py-4">Tap a product to add</div>
                </div>
                <div class="card-body border-top">
                    <div class="d-flex justify-content-between"><span>Subtotal</span><span id="pos-subtotal" class="fw-semibold">₱0.00</span></div>
                    <div class="d-flex justify-content-between fs-4 fw-bold text-success mt-2"><span>Total</span><span id="pos-total">₱0.00</span></div>
                </div>
                <div class="card-footer d-none d-lg-grid gap-2">
                    <button type="button" class="btn btn-success btn-lg" id="pos-checkout-lg" disabled>Checkout</button>
                    <button type="button" class="btn btn-outline-secondary" id="pos-clear">Clear</button>
                </div>
            </div>

            <div class="card shadow-sm pos-pnl mb-3">
                <div class="card-header bg-white fw-semibold">Today&apos;s summary</div>
                <div class="card-body">
                    <dl class="row mb-0 small">
                        <dt class="col-7">Sales today</dt><dd class="col-5 text-end">₱{{ number_format($today['total_sales'], 2) }}</dd>
                        <dt class="col-7">COGS today</dt><dd class="col-5 text-end">₱{{ number_format($today['total_cogs'], 2) }}</dd>
                        <dt class="col-7">Gross profit</dt><dd class="col-5 text-end text-success">₱{{ number_format($today['gross_profit'], 2) }}</dd>
                        <dt class="col-7">Margin</dt><dd class="col-5 text-end">{{ $today['gross_margin_percent'] }}%</dd>
                        <dt class="col-7">Orders</dt><dd class="col-5 text-end">{{ $today['order_count'] }}</dd>
                    </dl>
                </div>
            </div>

            <div class="card shadow-sm pos-pnl">
                <div class="card-header bg-white fw-semibold">Period totals</div>
                <div class="card-body">
                    <dl class="row mb-0 small">
                        <dt class="col-7">This week</dt><dd class="col-5 text-end">₱{{ number_format($summary['week'], 2) }}</dd>
                        <dt class="col-7">This month</dt><dd class="col-5 text-end">₱{{ number_format($summary['month'], 2) }}</dd>
                    </dl>
                </div>
            </div>

            <div class="card shadow-sm pos-pnl mt-3">
                <div class="card-header bg-white fw-semibold">Profit &amp; loss (month)</div>
                <div class="card-body">
                    <dl class="row mb-2 small">
                        <dt class="col-7">Total sales</dt><dd class="col-5 text-end">₱{{ number_format($pnl['sales_month'], 2) }}</dd>
                        <dt class="col-7">Total COGS</dt><dd class="col-5 text-end">₱{{ number_format($pnl['cogs_month'], 2) }}</dd>
                        <dt class="col-7">Gross profit</dt><dd class="col-5 text-end text-success">₱{{ number_format($pnl['gross_profit_month'], 2) }}</dd>
                        <dt class="col-7">Gross margin</dt><dd class="col-5 text-end fw-bold">{{ $pnl['margin_month'] }}%</dd>
                    </dl>
                    <h6 class="small fw-semibold">Top sellers</h6>
                    <ul class="list-unstyled small mb-0">
                        @forelse ($pnl['top_products'] as $tp)
                            <li>{{ $tp->product_name }} — {{ $tp->units }} sold</li>
                        @empty
                            <li class="text-muted">No sales yet</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="pos-checkout-bar d-lg-none">
    <div class="d-flex justify-content-between align-items-center gap-3">
        <div><div class="small text-muted">Total</div><div class="fs-4 fw-bold text-success" id="pos-total-bar">₱0.00</div></div>
        <button type="button" class="btn btn-success btn-lg px-4" id="pos-checkout-bar" disabled>Checkout</button>
    </div>
</div>

<div class="modal fade" id="checkoutModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Complete sale</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body text-center">
                <p class="fs-3 fw-bold text-success" id="modal-total">₱0.00</p>
                <button type="button" class="btn btn-success btn-lg w-100 payment-pick active" data-pay="cash">Cash</button>
            </div>
            <div class="modal-footer d-grid">
                <button type="button" class="btn btn-success btn-lg" id="pos-confirm"><i class="fa-solid fa-check me-1"></i>Confirm &amp; pay</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
const cart = new Map();
const fmt = n => '₱' + n.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
let paymentMethod = 'cash';
const DAY_LOCKED = @json($dayLocked);

if (DAY_LOCKED) {
    document.querySelectorAll('.pos-add-btn').forEach(b => b.disabled = true);
}

function renderCart() {
    const el = document.getElementById('pos-cart');
    const btns = [document.getElementById('pos-checkout-lg'), document.getElementById('pos-checkout-bar')];
    const clearBtns = [document.getElementById('pos-clear'), document.getElementById('pos-clear-bar')];
    const countEl = document.getElementById('pos-cart-count');
    let total = 0;
    let itemCount = 0;
    if (cart.size === 0) {
        el.innerHTML = '<div class="text-center text-muted py-4">Tap a product to add</div>';
        btns.forEach(b => b && (b.disabled = true));
        clearBtns.forEach(b => b && (b.disabled = true));
        if (countEl) countEl.textContent = '0';
    } else {
        el.innerHTML = '';
        cart.forEach((item, id) => {
            total += item.price * item.qty;
            itemCount += item.qty;
            const li = document.createElement('div');
            li.className = 'border-bottom py-2';
            li.innerHTML = `<div class="fw-semibold">${item.name}</div>
                <div class="d-flex justify-content-between align-items-center mt-1">
                    <div class="btn-group"><button class="btn btn-outline-secondary pos-qty-btn" data-act="dec" data-id="${id}">−</button>
                    <span class="px-2">${item.qty}</span><button class="btn btn-outline-secondary pos-qty-btn" data-act="inc" data-id="${id}">+</button></div>
                    <div class="text-end"><div class="small text-muted">${fmt(item.price)}</div><strong>${fmt(item.price * item.qty)}</strong></div>
                </div>`;
            el.appendChild(li);
        });
        btns.forEach(b => b && (b.disabled = DAY_LOCKED));
        clearBtns.forEach(b => b && (b.disabled = DAY_LOCKED));
        if (countEl) countEl.textContent = String(itemCount);
    }
    ['pos-subtotal','pos-total','pos-total-bar','modal-total'].forEach(id => {
        const n = document.getElementById(id);
        if (n) n.textContent = fmt(total);
    });
}

document.querySelectorAll('.pos-add-btn').forEach(btn => btn.addEventListener('click', () => {
    if (DAY_LOCKED) return;
    const id = btn.dataset.id;
    const cur = cart.get(id) || { operator_product_id: parseInt(id,10), name: btn.dataset.name, price: parseFloat(btn.dataset.price), qty: 0 };
    cur.qty++; cart.set(id, cur); renderCart();
}));

document.getElementById('pos-cart').addEventListener('click', e => {
    const btn = e.target.closest('button'); if (!btn) return;
    const item = cart.get(btn.dataset.id); if (!item) return;
    if (btn.dataset.act === 'inc') item.qty++;
    if (btn.dataset.act === 'dec') { item.qty--; if (item.qty <= 0) cart.delete(btn.dataset.id); }
    renderCart();
});

const clearCart = () => { cart.clear(); renderCart(); };
document.getElementById('pos-clear')?.addEventListener('click', clearCart);
document.getElementById('pos-clear-bar')?.addEventListener('click', clearCart);
const modal = new bootstrap.Modal(document.getElementById('checkoutModal'));
const openCheckout = () => { if (DAY_LOCKED) { alert('Sales are locked for today. Contact your administrator.'); return; } if (cart.size) modal.show(); };
document.getElementById('pos-checkout-lg')?.addEventListener('click', openCheckout);
document.getElementById('pos-checkout-bar')?.addEventListener('click', openCheckout);

document.getElementById('pos-confirm').addEventListener('click', async () => {
    const items = [...cart.values()].map(i => ({ operator_product_id: i.operator_product_id, qty: i.qty }));
    const res = await fetch('{{ route('operator.pos.checkout') }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
        body: JSON.stringify({ items, payment_method: paymentMethod }),
    });
    const data = await res.json();
    if (!res.ok) { alert(data.message || 'Failed'); return; }
    modal.hide();
    alert(`Sale complete! ${fmt(data.totals.revenue)} (profit ${fmt(data.totals.profit)})`);
    location.reload();
});

document.getElementById('pos-search').addEventListener('input', e => {
    const q = e.target.value.trim().toLowerCase();
    document.querySelectorAll('.pos-product-wrap').forEach(w => {
        w.style.display = !q || (w.dataset.name || '').includes(q) ? '' : 'none';
    });
});
renderCart();
</script>
@endsection
