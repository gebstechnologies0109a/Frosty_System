@php
    /** @var \Illuminate\Contracts\Pagination\LengthAwarePaginator $paginator */
    $perPageOptions = \App\Support\ListPage::PER_PAGE_OPTIONS;
    $currentPerPage = (int) request('per_page', $paginator->perPage());
    $pageName = $paginator->getPageName();
@endphp
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mt-3">
    <form method="get" class="d-flex align-items-center gap-2 mb-0">
        @foreach (request()->except(['per_page', 'page', $pageName]) as $key => $value)
            @if (is_array($value))
                @foreach ($value as $itemKey => $itemValue)
                    <input type="hidden" name="{{ $key }}[{{ $itemKey }}]" value="{{ $itemValue }}">
                @endforeach
            @else
                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
            @endif
        @endforeach
        <label class="form-label small text-muted mb-0 text-nowrap" for="per_page_{{ $pageName }}">Items per page</label>
        <select name="per_page" id="per_page_{{ $pageName }}" class="form-select form-select-sm" style="width: auto;" onchange="this.form.submit()">
            @foreach ($perPageOptions as $size)
                <option value="{{ $size }}" @selected($currentPerPage === $size)>{{ $size }}</option>
            @endforeach
        </select>
        <span class="small text-muted text-nowrap">
            {{ $paginator->firstItem() ?? 0 }}–{{ $paginator->lastItem() ?? 0 }} of {{ $paginator->total() }}
        </span>
    </form>
    @if ($paginator->hasPages())
        <div class="mb-0">
            {{ $paginator->withQueryString()->links('pagination::bootstrap-5') }}
        </div>
    @endif
</div>
