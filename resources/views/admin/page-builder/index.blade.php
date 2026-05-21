@extends('layouts.app')
@section('title', 'Page Builder')
@section('content')
@include('admin.partials.page-header', [
    'title' => 'Page Builder',
    'subtitle' => 'Select a page to edit its layout, then save to finalize changes',
    'actions' => '<a href="'.route('admin.page-builder.create').'" class="btn btn-primary">Create new page</a>',
])

<div class="alert alert-light border shadow-sm mb-4">
    <div class="fw-semibold mb-1">How it works</div>
    <ol class="mb-0 small text-muted ps-3">
        <li>Review all pages below — <strong>Position</strong> is the display order; <strong>Layout blocks</strong> show each block’s order and function on that page.</li>
        <li>Click <strong>Edit page</strong> to open the drag-and-drop builder.</li>
        <li>Reorder or change blocks, then click <strong>Save & return to list</strong> to finalize.</li>
    </ol>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Total pages</div>
                <div class="fs-3 fw-bold">{{ $pages->count() }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-8">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header fw-semibold bg-white">Available block functions</div>
            <div class="card-body py-2">
                <div class="d-flex flex-wrap gap-2">
                    @foreach ($blockTypes as $type => $meta)
                        <span class="badge text-bg-light border text-dark" title="{{ $meta['function'] }}">
                            {{ $meta['label'] }}
                        </span>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

<form method="post" action="{{ route('admin.page-builder.reorder') }}" id="pageOrderForm" @if($pages->isEmpty()) class="d-none" @endif>
        @csrf
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap gap-2">
                <span class="fw-semibold">All pages</span>
                <button type="submit" class="btn btn-sm btn-outline-primary">Save page order</button>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width:3rem">#</th>
                            <th>Page</th>
                            <th>Public URL</th>
                            <th>Layout blocks (position → function)</th>
                            <th>Last saved</th>
                            <th class="text-end" style="min-width:12rem">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="pagesSortable">
                    @foreach ($pages as $p)
                        <tr data-page-id="{{ $p->id }}">
                            <td class="text-muted">
                                <input type="hidden" name="order[]" value="{{ $p->id }}">
                                <span class="page-position fw-semibold">{{ $loop->iteration }}</span>
                                <button type="button" class="btn btn-link btn-sm text-muted p-0 ms-1 drag-handle" title="Drag to reorder page" aria-label="Reorder">⋮⋮</button>
                            </td>
                            <td>
                                <div class="fw-semibold">{{ $p->title }}</div>
                                <div class="small text-muted">{{ $p->blockCount() }} block{{ $p->blockCount() === 1 ? '' : 's' }}</div>
                            </td>
                            <td><code class="small">/p/{{ $p->slug }}</code></td>
                            <td>
                                @if ($p->blockCount() === 0)
                                    <span class="text-muted small">No blocks — edit to add content</span>
                                @else
                                    <ol class="list-unstyled mb-0 small">
                                        @foreach ($p->layoutOutline() as $block)
                                            <li class="mb-1">
                                                <span class="badge text-bg-secondary me-1">{{ $block['position'] }}</span>
                                                <span class="fw-medium">{{ $block['label'] }}</span>
                                                <span class="text-muted" title="{{ $block['function'] }}">— {{ $block['function'] }}</span>
                                                @if ($block['summary'] !== '—')
                                                    <span class="text-muted">({{ $block['summary'] }})</span>
                                                @endif
                                            </li>
                                        @endforeach
                                    </ol>
                                @endif
                            </td>
                            <td class="small text-muted text-nowrap">{{ $p->updated_at->format('M j, Y g:i A') }}</td>
                            <td class="text-end text-nowrap">
                                <a href="{{ route('admin.page-builder.edit', $p) }}" class="btn btn-sm btn-primary">Edit page</a>
                                <a href="{{ route('admin.page-builder.preview', $p) }}" class="btn btn-sm btn-outline-secondary">Preview</a>
                                <a href="{{ route('pages.show', $p->slug) }}" class="btn btn-sm btn-outline-secondary" target="_blank">View live</a>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </form>
@if ($pages->isEmpty())
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white fw-semibold">All pages</div>
        <div class="card-body text-center py-5">
            <p class="text-muted mb-3">No pages yet. Create your first page to get started.</p>
            <a href="{{ route('admin.page-builder.create') }}" class="btn btn-primary">Create new page</a>
        </div>
    </div>
@endif
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
const tbody = document.getElementById('pagesSortable');
if (tbody) {
    new Sortable(tbody, {
        handle: '.drag-handle',
        animation: 150,
        onEnd() {
            [...tbody.querySelectorAll('tr')].forEach((row, i) => {
                row.querySelector('.page-position').textContent = i + 1;
            });
        },
    });
}
</script>
@endpush
