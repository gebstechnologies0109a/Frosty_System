@php
    $priceRegion = auth()->user()->priceRegion();
@endphp
<form method="post" action="{{ $action }}" id="order-form">
    @csrf
    @if (!empty($distributors))
        <div class="mb-3">
            <label class="form-label">Distributor / Main</label>
            <select name="distributor_id" class="form-select" required>
                @foreach ($distributors as $d)
                    <option value="{{ $d->id }}">
                        {{ $d->name }}@if($d->is_main) (Main — Purchasing)@endif
                    </option>
                @endforeach
            </select>
        </div>
    @endif
    <p class="small text-muted mb-2">Pricing region: <strong>{{ $priceRegion->label() }}</strong></p>
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
    <p class="small text-muted">Softserve: 2 points (₱2 rebate per unit). All other categories: 0 points.</p>
    <button class="btn btn-primary">Submit order</button>
</form>
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
