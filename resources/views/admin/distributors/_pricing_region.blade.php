@php
    $pricingRegions = $pricingRegions ?? \App\Enums\DistributorPricingRegion::cases();
    $selected = $selected ?? \App\Enums\DistributorPricingRegion::Luzon->value;
@endphp
<div class="mb-3">
    <label class="form-label fw-semibold">Pricing region</label>
    <select name="pricing_region" class="form-select" required>
        @foreach ($pricingRegions as $region)
            <option value="{{ $region->value }}" @selected(old('pricing_region', $selected ?? 'luzon') === $region->value)>
                {{ $region->label() }}
            </option>
        @endforeach
    </select>
    <div class="form-text">Operators under this distributor inherit this region for POS and store pricing.</div>
</div>
