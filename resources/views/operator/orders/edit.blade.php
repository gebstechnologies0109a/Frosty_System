@extends('layouts.operator')
@section('header_title', 'Edit order #'.$order->id)
@section('title', 'Edit Order')
@section('content')
<h1 class="h4 mb-3">Edit order #{{ $order->id }}</h1>
@if (session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif
<form method="post" action="{{ route('operator.orders.update', $order) }}" id="order-form" enctype="multipart/form-data">
    @csrf
    @method('PUT')
    @php
        $pricingRegionLabel = $order->distributor?->pricingRegion()->label() ?? $order->price_region?->label();
    @endphp
    <div class="mb-3">
        <label class="form-label">Distributor / Main</label>
        <select name="distributor_id" id="distributor-select" class="form-select" required>
            @foreach ($distributors as $d)
                <option value="{{ $d->id }}" @selected($order->distributor_id === $d->id)>
                    {{ $d->name }}@if($d->is_main) (Main — Purchasing)@endif
                </option>
            @endforeach
        </select>
    </div>
    <p class="small text-muted mb-2">Pricing region: <strong id="pricing-region-label">{{ $pricingRegionLabel }}</strong></p>

    <div class="mb-3">
        <label class="form-label" for="notes">Order notes</label>
        <textarea name="notes" id="notes" class="form-control" rows="3" maxlength="2000" placeholder="Optional notes for your distributor">{{ old('notes', $order->notes) }}</textarea>
        @error('notes')<div class="text-danger small">{{ $message }}</div>@enderror
    </div>

    <div class="mb-2 small text-muted">Search products by name (type at least 2 characters).</div>
    <div id="lines" class="order-lines"></div>
    <template id="product-line-template">
        <div class="row g-2 mb-3 line-row align-items-end">
            <div class="col-md-7">
                <label class="form-label small mb-1">Product</label>
                <div class="product-picker position-relative">
                    <input type="hidden" class="product-id-input" required>
                    <input type="search" class="form-control product-search-input" placeholder="Search product…" autocomplete="off" inputmode="search">
                    <div class="product-search-results list-group shadow-sm d-none"></div>
                    <div class="product-selected badge text-bg-light border mt-1 d-none"></div>
                </div>
            </div>
            <div class="col-md-3">
                <label class="form-label small mb-1">Qty</label>
                <input type="number" class="form-control line-qty" value="1" min="1" required>
            </div>
            <div class="col-md-2">
                <button type="button" class="btn btn-outline-danger btn-sm w-100 remove-line-btn d-none" title="Remove line">×</button>
            </div>
        </div>
    </template>
    <button type="button" class="btn btn-outline-secondary btn-sm mb-3" id="add-product-line">+ Add line</button>

    <div class="mb-3">
        <label class="form-label" for="payment_proof">Upload / replace proof of payment</label>
        <input type="file" name="payment_proof" id="payment_proof" class="form-control"
               accept="image/*,application/pdf">
        <div class="form-text">JPG, PNG, HEIC, or PDF. Max 5 MB. Leave empty to keep current proof.</div>
        @error('payment_proof')<div class="text-danger small">{{ $message }}</div>@enderror
    </div>

    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary">Save changes</button>
        <a href="{{ route('operator.orders.show', $order) }}" class="btn btn-outline-secondary">Cancel</a>
    </div>
</form>
@endsection

@push('head')
<style>
    .product-picker .product-search-results {
        position: absolute;
        left: 0;
        right: 0;
        top: 100%;
        z-index: 1050;
        max-height: 240px;
        overflow-y: auto;
        margin-top: 2px;
    }
    .product-picker .list-group-item { cursor: pointer; font-size: 0.9rem; }
</style>
@endpush

@push('scripts')
<script>
(function () {
    const searchUrl = @json($productSearchUrl);
    const distributorRegionLabels = @json($distributorRegionLabels);
    const initialLines = @json($initialLines);
    const distributorSelect = document.getElementById('distributor-select');
    const pricingRegionLabel = document.getElementById('pricing-region-label');
    const linesEl = document.getElementById('lines');
    const template = document.getElementById('product-line-template');
    let lineIndex = 0;

    function selectedDistributorId() {
        return distributorSelect ? distributorSelect.value : '';
    }

    function updatePricingRegionLabel() {
        if (!pricingRegionLabel || !distributorSelect) return;
        const label = distributorRegionLabels[distributorSelect.value];
        if (label) pricingRegionLabel.textContent = label;
    }

    if (distributorSelect) {
        distributorSelect.addEventListener('change', updatePricingRegionLabel);
    }

    function bindPicker(row, index, preset) {
        const picker = row.querySelector('.product-picker');
        const hidden = row.querySelector('.product-id-input');
        const searchInput = row.querySelector('.product-search-input');
        const results = row.querySelector('.product-search-results');
        const selected = row.querySelector('.product-selected');
        const qtyInput = row.querySelector('.line-qty');
        const removeBtn = row.querySelector('.remove-line-btn');

        hidden.name = `items[${index}][product_id]`;
        qtyInput.name = `items[${index}][qty]`;

        if (index > 0) {
            removeBtn.classList.remove('d-none');
            removeBtn.addEventListener('click', () => row.remove());
        }

        const hideResults = () => results.classList.add('d-none');

        function selectProduct(item) {
            hidden.value = item.id;
            searchInput.value = item.name;
            searchInput.dataset.selectedName = item.name;
            selected.textContent = `₱${Number(item.price).toFixed(2)} · ${item.points} pt${item.points === 1 ? '' : 's'} · ${item.category}`;
            selected.classList.remove('d-none');
            hideResults();
        }

        if (preset) {
            selectProduct({
                id: preset.product_id,
                name: preset.name,
                price: preset.price,
                points: preset.points,
                category: preset.category,
            });
            qtyInput.value = preset.qty;
        }

        const runSearch = debounce(async () => {
            const q = searchInput.value.trim();
            if (q.length < 2) {
                results.innerHTML = '';
                hideResults();
                return;
            }
            const params = new URLSearchParams({ q });
            const distributorId = selectedDistributorId();
            if (distributorId) params.set('distributor_id', distributorId);
            try {
                const res = await fetch(`${searchUrl}?${params}`, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                });
                const items = await res.json();
                results.innerHTML = '';
                items.forEach((item) => {
                    const btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = 'list-group-item list-group-item-action';
                    btn.textContent = item.text;
                    btn.addEventListener('click', () => selectProduct(item));
                    results.appendChild(btn);
                });
                if (!items.length) {
                    results.innerHTML = '<div class="list-group-item text-muted">No products found</div>';
                }
                results.classList.remove('d-none');
            } catch (e) {
                results.innerHTML = '<div class="list-group-item text-danger">Search unavailable</div>';
                results.classList.remove('d-none');
            }
        }, 280);

        searchInput.addEventListener('input', () => {
            if (hidden.value && searchInput.value !== searchInput.dataset.selectedName) {
                hidden.value = '';
                selected.classList.add('d-none');
            }
            runSearch();
        });

        document.addEventListener('click', (e) => {
            if (!picker.contains(e.target)) hideResults();
        });
    }

    function debounce(fn, ms) {
        let t;
        return (...args) => { clearTimeout(t); t = setTimeout(() => fn(...args), ms); };
    }

    function addLine(preset) {
        const row = template.content.cloneNode(true).querySelector('.line-row');
        linesEl.appendChild(row);
        bindPicker(row, lineIndex++, preset);
    }

    document.getElementById('add-product-line').addEventListener('click', () => addLine(null));

    if (initialLines.length) {
        initialLines.forEach((line) => addLine(line));
    } else {
        addLine(null);
    }
})();
</script>
@endpush
