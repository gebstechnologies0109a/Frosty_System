@php
    $filterOpen = $hasActiveFilters ?? false;
@endphp
<div class="accordion mb-4" id="product-filters-accordion">
    <div class="accordion-item shadow-sm">
        <h2 class="accordion-header">
            <button class="accordion-button {{ $filterOpen ? '' : 'collapsed' }}" type="button"
                data-bs-toggle="collapse" data-bs-target="#product-filters-panel"
                aria-expanded="{{ $filterOpen ? 'true' : 'false' }}" aria-controls="product-filters-panel">
                Product filters
                @if ($hasActiveFilters ?? false)
                    <span class="badge text-bg-primary ms-2">Active</span>
                @endif
            </button>
        </h2>
        <div id="product-filters-panel" class="accordion-collapse collapse {{ $filterOpen ? 'show' : '' }}"
            data-bs-parent="#product-filters-accordion">
            <div class="accordion-body">
                <form method="get" action="{{ route('admin.purchasing.products.index') }}" id="product-filters-form" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Category</label>
                        <select name="category" class="form-select form-select-sm">
                            <option value="">All categories</option>
                            @foreach ($productCategories as $cat)
                                <option value="{{ $cat->value }}" @selected(($filters['category'] ?? '') === $cat->value)>
                                    {{ $cat->label() }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Name search</label>
                        <input type="search" name="search" id="filter-search" class="form-control form-control-sm"
                            value="{{ $filters['search'] ?? '' }}" placeholder="Partial name…">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select form-select-sm">
                            <option value="">All</option>
                            <option value="active" @selected(($filters['status'] ?? '') === 'active')>Active</option>
                            <option value="inactive" @selected(($filters['status'] ?? '') === 'inactive')>Inactive</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Points</label>
                        <select name="points" class="form-select form-select-sm">
                            <option value="">All</option>
                            <option value="0" @selected(($filters['points'] ?? '') === '0' || ($filters['points'] ?? '') === 0)>0 points</option>
                            <option value="2" @selected(($filters['points'] ?? '') === '2' || ($filters['points'] ?? '') === 2)>2 points</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Inventory</label>
                        <select name="inventory" class="form-select form-select-sm">
                            <option value="">All</option>
                            <option value="in_stock" @selected(($filters['inventory'] ?? '') === 'in_stock')>In stock</option>
                            <option value="out_of_stock" @selected(($filters['inventory'] ?? '') === 'out_of_stock')>Out of stock</option>
                        </select>
                    </div>

                    <div class="col-12"><hr class="my-1"><span class="small text-muted">Price range (₱)</span></div>
                    <div class="col-md-4">
                        <label class="form-label small mb-1">Luzon min / max</label>
                        <div class="input-group input-group-sm">
                            <input type="number" step="0.01" min="0" name="price_luzon_min" class="form-control"
                                placeholder="Min" value="{{ $filters['price_luzon_min'] ?? '' }}">
                            <input type="number" step="0.01" min="0" name="price_luzon_max" class="form-control"
                                placeholder="Max" value="{{ $filters['price_luzon_max'] ?? '' }}">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small mb-1">Davao min / max</label>
                        <div class="input-group input-group-sm">
                            <input type="number" step="0.01" min="0" name="price_davao_min" class="form-control"
                                placeholder="Min" value="{{ $filters['price_davao_min'] ?? '' }}">
                            <input type="number" step="0.01" min="0" name="price_davao_max" class="form-control"
                                placeholder="Max" value="{{ $filters['price_davao_max'] ?? '' }}">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small mb-1">Tacloban min / max</label>
                        <div class="input-group input-group-sm">
                            <input type="number" step="0.01" min="0" name="price_tacloban_min" class="form-control"
                                placeholder="Min" value="{{ $filters['price_tacloban_min'] ?? '' }}">
                            <input type="number" step="0.01" min="0" name="price_tacloban_max" class="form-control"
                                placeholder="Max" value="{{ $filters['price_tacloban_max'] ?? '' }}">
                        </div>
                    </div>

                    <div class="col-12 d-flex gap-2 flex-wrap">
                        <button type="submit" class="btn btn-primary btn-sm">Apply filters</button>
                        <a href="{{ route('admin.purchasing.products.index') }}" class="btn btn-outline-secondary btn-sm" id="reset-filters">Reset filters</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
