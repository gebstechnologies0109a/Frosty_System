@props(['action', 'placeholder' => 'Search…'])
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-3">
        <form method="get" action="{{ $action }}" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label small mb-1">Search</label>
                <input type="search" name="q" class="form-control form-control-sm" value="{{ request('q') }}" placeholder="{{ $placeholder }}">
            </div>
            {{ $slot }}
            <div class="col-auto d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                <a href="{{ $action }}" class="btn btn-outline-secondary btn-sm">Reset</a>
            </div>
        </form>
    </div>
</div>
