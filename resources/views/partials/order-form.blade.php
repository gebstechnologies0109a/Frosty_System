@php
    $priceRegion = auth()->user()->priceRegion();
    $distributorRegionLabels = ! empty($distributors)
        ? $distributors->mapWithKeys(fn ($d) => [$d->id => $d->pricingRegion()->label()])->all()
        : [];
    $pricingRegionLabel = ! empty($distributors)
        ? ($distributors->first()->pricingRegion()->label())
        : $priceRegion->label();
@endphp
<form method="post" action="{{ $action }}" id="order-form" @if(!empty($showPaymentProof)) enctype="multipart/form-data" @endif>
    @csrf
    @if (!empty($distributors))
        <div class="mb-3">
            <label class="form-label">Distributor / Main</label>
            <select name="distributor_id" id="distributor-select" class="form-select" required>
                @foreach ($distributors as $d)
                    <option value="{{ $d->id }}">
                        {{ $d->name }}@if($d->is_main) (Main — Purchasing)@endif
                    </option>
                @endforeach
            </select>
        </div>
    @endif
    <p class="small text-muted mb-2">Pricing region: <strong id="pricing-region-label">{{ $pricingRegionLabel }}</strong></p>

    @if (!empty($useProductSearch))
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
    @else
        <div id="lines">
            <div class="row g-2 mb-2 line-row">
                <div class="col-md-7">
                    <select name="items[0][product_id]" class="form-select product-select" required>
                        <option value="">Product…</option>
                        @foreach ($products as $p)
                            @php $unitPrice = $p->priceForRegion($priceRegion); @endphp
                            <option value="{{ $p->id }}" data-price="{{ $unitPrice }}">
                                {{ $p->name }} ({{ $p->points }} pts) — ₱{{ number_format($unitPrice, 2) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3"><input type="number" name="items[0][qty]" class="form-control" value="1" min="1" required></div>
            </div>
        </div>
        <button type="button" class="btn btn-outline-secondary btn-sm mb-3" onclick="addLine()">+ Add line</button>
    @endif

    @if (!empty($showPaymentProof))
        <div class="mb-3">
            <label class="form-label" for="payment_proof">Upload Proof of Payment</label>
            <input type="file" name="payment_proof" id="payment_proof" class="form-control"
                   accept="image/*,application/pdf">
            <div class="form-text">JPG, PNG, HEIC, or PDF. Max 5 MB. Optional at submit; required before admin approval.</div>
            @error('payment_proof')<div class="text-danger small">{{ $message }}</div>@enderror
        </div>
    @endif
    <p class="small text-muted">Softserve: 2 points (₱2 rebate per unit). All other categories: 0 points.</p>
    <button class="btn btn-primary">Submit order</button>
</form>

@if (!empty($useProductSearch))
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
        .product-picker .list-group-item {
            cursor: pointer;
            font-size: 0.9rem;
        }
        .product-picker .list-group-item:active,
        .product-picker .list-group-item:hover {
            background: var(--bs-primary-bg-subtle);
        }
    </style>
    @endpush
    @push('scripts')
    <script>
    (function () {
        const searchUrl = @json($productSearchUrl);
        const distributorRegionLabels = @json($distributorRegionLabels ?? []);
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

        function clearLineProducts() {
            linesEl.querySelectorAll('.line-row').forEach((row) => {
                const hidden = row.querySelector('.product-id-input');
                const searchInput = row.querySelector('.product-search-input');
                const selected = row.querySelector('.product-selected');
                if (hidden) hidden.value = '';
                if (searchInput) {
                    searchInput.value = '';
                    delete searchInput.dataset.selectedName;
                }
                if (selected) selected.classList.add('d-none');
            });
        }

        if (distributorSelect) {
            distributorSelect.addEventListener('change', () => {
                updatePricingRegionLabel();
                clearLineProducts();
            });
        }

        function debounce(fn, ms) {
            let t;
            return (...args) => {
                clearTimeout(t);
                t = setTimeout(() => fn(...args), ms);
            };
        }

        function bindPicker(row, index) {
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

            const runSearch = debounce(async () => {
                const q = searchInput.value.trim();
                if (q.length < 2) {
                    results.innerHTML = '';
                    hideResults();
                    return;
                }
                try {
                    const params = new URLSearchParams({ q });
                    const distributorId = selectedDistributorId();
                    if (distributorId) params.set('distributor_id', distributorId);
                    const res = await fetch(`${searchUrl}?${params}`, {
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                        credentials: 'same-origin',
                    });
                    if (!res.ok) throw new Error('Search failed');
                    const items = await res.json();
                    results.innerHTML = '';
                    if (!items.length) {
                        results.innerHTML = '<div class="list-group-item text-muted">No products found</div>';
                    } else {
                        items.forEach((item) => {
                            const btn = document.createElement('button');
                            btn.type = 'button';
                            btn.className = 'list-group-item list-group-item-action';
                            btn.textContent = item.text;
                            btn.dataset.id = item.id;
                            btn.dataset.name = item.name;
                            btn.dataset.price = item.price;
                            btn.dataset.points = item.points;
                            btn.dataset.category = item.category;
                            btn.addEventListener('click', () => selectProduct(item));
                            results.appendChild(btn);
                        });
                    }
                    results.classList.remove('d-none');
                } catch (e) {
                    results.innerHTML = '<div class="list-group-item text-danger">Search unavailable</div>';
                    results.classList.remove('d-none');
                }
            }, 280);

            function selectProduct(item) {
                hidden.value = item.id;
                searchInput.value = item.name;
                searchInput.dataset.selectedName = item.name;
                selected.textContent = `₱${Number(item.price).toFixed(2)} · ${item.points} pt${item.points === 1 ? '' : 's'} · ${item.category}`;
                selected.classList.remove('d-none');
                hideResults();
            }

            searchInput.addEventListener('input', () => {
                if (hidden.value && searchInput.value !== searchInput.dataset.selectedName) {
                    hidden.value = '';
                    selected.classList.add('d-none');
                }
                runSearch();
            });

            searchInput.addEventListener('focus', () => {
                if (searchInput.value.trim().length >= 2) runSearch();
            });

            document.addEventListener('click', (e) => {
                if (!picker.contains(e.target)) hideResults();
            });
        }

        function addLine() {
            const row = template.content.cloneNode(true).querySelector('.line-row');
            linesEl.appendChild(row);
            bindPicker(row, lineIndex++);
            updateRemoveButtons();
        }

        function updateRemoveButtons() {
            const rows = linesEl.querySelectorAll('.line-row');
            rows.forEach((row, i) => {
                const btn = row.querySelector('.remove-line-btn');
                if (i === 0) {
                    btn.classList.add('d-none');
                } else {
                    btn.classList.remove('d-none');
                }
            });
        }

        document.getElementById('add-product-line').addEventListener('click', addLine);
        addLine();
    })();
    </script>
    @endpush
@else
<script>
let lineIndex = 1;
function addLine() {
    const tpl = document.querySelector('.line-row').cloneNode(true);
    tpl.querySelector('select').name = `items[${lineIndex}][product_id]`;
    tpl.querySelector('select').classList.add('product-select');
    tpl.querySelector('input').name = `items[${lineIndex}][qty]`;
    tpl.querySelector('input').value = '1';
    document.getElementById('lines').appendChild(tpl);
    lineIndex++;
}
</script>
@endif
