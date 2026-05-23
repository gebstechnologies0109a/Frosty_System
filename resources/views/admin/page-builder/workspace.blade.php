@extends('layouts.app')
@section('title', 'Page Builder')
@section('content')
@include('admin.partials.page-header', [
    'title' => 'Page Builder',
    'subtitle' => 'Visual workspace for composing admin and public pages.',
    'actions' => '<a href="'.route('admin.dashboard').'" class="btn btn-outline-secondary btn-sm">← Back to dashboard</a>
        <a href="'.route('admin.page-builder.manage').'" class="btn btn-outline-primary btn-sm">All pages</a>',
])

<div id="pb-toast" class="position-fixed top-0 end-0 p-3" style="z-index:1080"></div>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-body d-flex flex-wrap gap-2 align-items-center">
        <button type="button" class="btn btn-primary btn-sm" id="pb-new">+ New Page</button>
        <button type="button" class="btn btn-outline-primary btn-sm" id="pb-save">Save Draft</button>
        <button type="button" class="btn btn-success btn-sm" id="pb-publish">Publish</button>
        <button type="button" class="btn btn-outline-secondary btn-sm" id="pb-preview">Preview</button>
        <button type="button" class="btn btn-outline-secondary btn-sm" id="pb-template">Apply template</button>
        <button type="button" class="btn btn-outline-danger btn-sm" id="pb-delete">Delete</button>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-6 col-lg-4">
        <label class="form-label small fw-semibold">Select a page</label>
        <select id="pb-page-select" class="form-select form-select-sm">
            <option value="">— Choose page —</option>
            @foreach ($pages as $p)
                <option value="{{ $p['id'] }}" @selected($p['id'] === $initialPageId)>{{ $p['name'] }} ({{ $p['slug'] }})</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-6 col-lg-4">
        <label class="form-label small fw-semibold">Page name</label>
        <input type="text" id="pb-name" class="form-control form-control-sm" placeholder="Page title">
    </div>
    <div class="col-md-6 col-lg-4">
        <label class="form-label small fw-semibold">Slug</label>
        <input type="text" id="pb-slug" class="form-control form-control-sm" placeholder="page-slug" pattern="[a-z0-9]+(?:-[a-z0-9]+)*">
    </div>
    <div class="col-12">
        <label class="form-label small fw-semibold">Description</label>
        <input type="text" id="pb-description" class="form-control form-control-sm" placeholder="Optional description">
    </div>
</div>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white fw-semibold">Templates</div>
    <div class="card-body d-flex flex-wrap gap-2">
        @foreach ($templates as $tpl)
            <button type="button" class="btn btn-sm btn-outline-primary pb-apply-template" data-template="{{ $tpl['key'] }}">{{ $tpl['label'] }}</button>
        @endforeach
    </div>
</div>

<div class="row g-3 page-builder-workspace">
    <div class="col-lg-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white fw-semibold">Components</div>
            <div class="card-body d-grid gap-1 pb-palette">
                @foreach ($components as $comp)
                    <button type="button" class="btn btn-sm btn-outline-primary text-start pb-add-component" data-type="{{ $comp['type'] }}">{{ $comp['label'] }}</button>
                @endforeach
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white fw-semibold">Canvas</div>
            <div class="card-body overflow-auto" style="min-height:420px">
                <div id="pb-canvas" class="d-flex flex-column gap-3"></div>
                <p id="pb-canvas-empty" class="text-muted text-center py-5 mb-0">Select or create a page, then add components.</p>
            </div>
        </div>
    </div>
    <div class="col-lg-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white fw-semibold">Properties</div>
            <div class="card-body" id="pb-properties">
                <p class="text-muted small mb-0">Select a component to edit its properties.</p>
            </div>
        </div>
    </div>
</div>
@endsection

@push('head')
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
<style>
.page-builder-workspace .pb-canvas-item { border: 1px dashed #dee2e6; border-radius: .5rem; padding: .75rem; background: #fff; }
.page-builder-workspace .pb-canvas-item.is-selected { border-color: #0d6efd; box-shadow: 0 0 0 2px rgba(13,110,253,.15); }
.page-builder-workspace .pb-preview-wrap { pointer-events: none; transform: scale(.95); transform-origin: top left; }
</style>
@endpush

@push('scripts')
<script>
window.PageBuilderWorkspace = {
    routes: @json($routes),
    csrf: @json(csrf_token()),
    componentDefaults: @json(collect(config('page-builder.components', []))->mapWithKeys(fn ($m, $k) => [$k => $m['defaults'] ?? []])),
    chartsInitialized: false,
};
</script>
<script src="{{ asset('js/page-builder-workspace.js') }}?v=1"></script>
@endpush
