@php
    $regional = $product->exists ? $product->regionalPrices() : [];
@endphp
<div class="mb-3">
    <label class="form-label">Product name</label>
    <input name="name" class="form-control" value="{{ old('name', $product->name) }}" required>
</div>
<div class="mb-3">
    <label class="form-label">Category</label>
    <select name="category" class="form-select" id="product-category">
        @foreach ($categories as $cat)
            <option value="{{ $cat->value }}" @selected(old('category', $product->category?->value) === $cat->value)>
                {{ $cat->label() }}
            </option>
        @endforeach
    </select>
</div>
<div class="mb-3">
    <label class="form-label">Points</label>
    <input type="number" name="points" id="product-points" class="form-control" min="0"
        value="{{ old('points', $product->points ?? 2) }}" required>
    <div class="form-text">Softserve must be 2 points; all other categories are 0.</div>
</div>
<fieldset class="border rounded p-3 mb-3">
    <legend class="float-none w-auto px-2 fs-6 mb-0">Regional prices (VAT-exclusive ₱)</legend>
    <div class="row g-3 mt-1">
        @foreach ($regions as $region)
            <div class="col-md-4">
                <label class="form-label">{{ $region->label() }}</label>
                <input type="number" step="0.01" min="0" name="price_{{ $region->value }}" class="form-control" required
                    value="{{ old('price_'.$region->value, $regional[$region->value] ?? 0) }}">
            </div>
        @endforeach
    </div>
</fieldset>
<div class="mb-3">
    <label class="form-label">Status</label>
    <select name="status" class="form-select">
        <option value="active" @selected(old('status', $product->status ?? 'active') === 'active')>Active</option>
        <option value="inactive" @selected(old('status', $product->status) === 'inactive')>Inactive</option>
    </select>
</div>
<script>
document.getElementById('product-category')?.addEventListener('change', function () {
    const pts = document.getElementById('product-points');
    if (!pts) return;
    if (this.value === 'softserve') {
        pts.value = pts.value === '0' || pts.value === '' ? '2' : pts.value;
        pts.min = 2;
    } else {
        pts.value = '0';
        pts.readOnly = true;
    }
});
</script>
