@extends('layouts.app')
@section('title', $isNew ? 'New Page' : 'Edit Page')
@section('content')
@include('admin.partials.page-header', [
    'title' => $isNew ? 'New Page' : 'Edit: '.$page->title,
    'subtitle' => $isNew ? 'Set title and slug, add blocks, then save' : ($page->route_name ? 'Linked route: '.$page->route_name.' ('.$page->path.')' : 'Custom page: /p/'.$page->slug),
    'actions' => '<a href="'.route('admin.page-builder.index').'" class="btn btn-outline-secondary">← All pages</a>'
        .($isNew ? '' : ' <a href="'.$page->liveUrl().'" class="btn btn-outline-secondary" target="_blank">View live</a>
        <a href="'.route('admin.page-builder.preview', $page).'" class="btn btn-outline-secondary">Preview</a>'),
])

@if (! $isNew && $page->blockCount() > 0)
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-2">
        <div class="small text-muted mb-1">Current layout on this page</div>
        <div class="d-flex flex-wrap gap-2">
            @foreach ($page->layoutOutline() as $block)
                <span class="badge text-bg-light border text-dark" title="{{ $block['function'] }}">
                    {{ $block['position'] }}. {{ $block['label'] }}
                </span>
            @endforeach
        </div>
    </div>
</div>
@endif
<form id="pageForm" method="post" action="{{ $isNew ? route('admin.page-builder.store') : route('admin.page-builder.update', $page) }}">
    @csrf
    @if (! $isNew) @method('PUT') @endif
    <input type="hidden" name="layout_json" id="layoutJson">
    <div class="row g-3">
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body">
                    <label class="form-label">Title</label>
                    <input type="text" name="title" class="form-control" value="{{ old('title', $page->title) }}" required>
                    <label class="form-label mt-2">Slug</label>
                    <input type="text" name="slug" class="form-control" value="{{ old('slug', $page->slug) }}" pattern="[a-z0-9]+(?:-[a-z0-9]+)*" required placeholder="my-page" {{ $page->is_system ? 'readonly' : '' }}>
                    <label class="form-label mt-2">Status</label>
                    <select name="status" class="form-select" required>
                        @foreach (\App\Enums\AdminPageStatus::cases() as $s)
                            <option value="{{ $s->value }}" @selected(old('status', $page->status->value) === $s->value)>{{ $s->label() }}</option>
                        @endforeach
                    </select>
                    @if ($page->is_system)
                        <p class="small text-muted mt-2 mb-0">System page — linked to <code>{{ $page->path }}</code></p>
                        <input type="hidden" name="route_name" value="{{ $page->route_name }}">
                        <input type="hidden" name="path" value="{{ $page->path }}">
                    @else
                        <label class="form-label mt-2">Path (optional)</label>
                        <input type="text" name="path" class="form-control" value="{{ old('path', $page->path) }}" placeholder="/admin/custom">
                        <p class="small text-muted mt-1">Custom pages: /p/<span id="slugPreview">{{ old('slug', $page->slug) ?: 'slug' }}</span></p>
                    @endif
                </div>
            </div>
            <div class="card border-0 shadow-sm">
                <div class="card-header fw-semibold">Add block</div>
                <div class="card-body d-grid gap-1">
                    @foreach (['text'=>'Text','card'=>'Card','table'=>'Table','button'=>'Button','chart'=>'Chart','form'=>'Form','divider'=>'Divider','spacer'=>'Spacer','html'=>'HTML','script'=>'Script'] as $type => $label)
                        <button type="button" class="btn btn-sm btn-outline-primary add-block" data-type="{{ $type }}">{{ $label }}</button>
                    @endforeach
                </div>
            </div>
        </div>
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header fw-semibold d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <span>Layout builder <span class="text-muted fw-normal small">(top = position 1 on page)</span></span>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-outline-primary btn-sm">Save</button>
                        @if (! $isNew)
                            <button type="submit" name="finish" value="1" class="btn btn-primary btn-sm">Save & return to list</button>
                        @else
                            <button type="submit" class="btn btn-primary btn-sm">Save page</button>
                        @endif
                    </div>
                </div>
                <div class="card-body">
                    <div id="blocksCanvas" class="vstack gap-2 min-vh-25"></div>
                    <p id="emptyHint" class="text-muted text-center py-4 mb-0">Add blocks from the left panel.</p>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection
@push('head')
<link href="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.css" rel="stylesheet" onerror="this.remove()">
@endpush
@push('scripts')
@php
    $builderBlockKeys = array_keys(\App\Models\AdminPage::BLOCK_TYPES);
    $builderBlocks = $page->blocks();
@endphp
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
const blockTypes = @json($builderBlockKeys);
let blocks = @json($builderBlocks);
const canvas = document.getElementById('blocksCanvas');
const emptyHint = document.getElementById('emptyHint');
const slugInput = document.querySelector('[name=slug]');

slugInput?.addEventListener('input', () => {
    document.getElementById('slugPreview').textContent = slugInput.value || 'slug';
});

function uid() {
    return 'b-' + Math.random().toString(36).slice(2, 10);
}

function defaultBlock(type) {
    const id = uid();
    const map = {
        text: { id, type, content: 'Text content' },
        card: { id, type, title: 'Card title', body: 'Card body' },
        table: { id, type, headers: ['Column A', 'Column B'], rows: [['Row 1', 'Value'], ['Row 2', 'Value']] },
        button: { id, type, label: 'Click me', url: '#' },
        chart: { id, type, title: 'Chart', content: 'Sample data' },
        form: { id, type, title: 'Form', content: 'Form description' },
        divider: { id, type },
        spacer: { id, type },
        html: { id, type, html: '<p>Custom HTML</p>' },
        script: { id, type, script: '' },
    };
    return map[type] || { id, type, content: '' };
}

function renderBlockEditor(block) {
    const wrap = document.createElement('div');
    wrap.className = 'border rounded p-3 bg-white';
    wrap.dataset.id = block.id;
    wrap.innerHTML = `<div class="d-flex justify-content-between align-items-center mb-2">
        <span class="badge text-bg-secondary">${block.type}</span>
        <button type="button" class="btn btn-sm btn-outline-danger remove-block">Remove</button>
    </div><div class="block-fields"></div>`;
    const fields = wrap.querySelector('.block-fields');
    const addField = (label, name, value, rows = 1) => {
        const g = document.createElement('div');
        g.className = 'mb-2';
        g.innerHTML = `<label class="form-label small">${label}</label>`;
        const el = rows > 1 ? document.createElement('textarea') : document.createElement('input');
        el.className = 'form-control form-control-sm';
        el.dataset.field = name;
        el.value = value ?? '';
        if (rows > 1) { el.rows = rows; }
        g.appendChild(el);
        fields.appendChild(g);
    };

    switch (block.type) {
        case 'text': addField('Content', 'content', block.content, 3); break;
        case 'card': addField('Title', 'title', block.title); addField('Body', 'body', block.body, 3); break;
        case 'table':
            addField('Headers (comma-separated)', 'headers', (block.headers || []).join(', '));
            addField('Rows (one per line, cells comma-separated)', 'rows', (block.rows || []).map(r => r.join(', ')).join('\n'), 4);
            break;
        case 'button': addField('Label', 'label', block.label); addField('URL', 'url', block.url); break;
        case 'chart': addField('Title', 'title', block.title); addField('Content', 'content', block.content, 2); break;
        case 'form': addField('Title', 'title', block.title); addField('Content', 'content', block.content, 2); break;
        case 'html': addField('HTML', 'html', block.html, 4); break;
        case 'script': addField('Script', 'script', block.script, 3); break;
        default: fields.innerHTML = '<p class="small text-muted mb-0">No settings for this block.</p>';
    }

    wrap.querySelector('.remove-block').addEventListener('click', () => {
        blocks = blocks.filter(b => b.id !== block.id);
        renderAll();
    });

    fields.querySelectorAll('[data-field]').forEach(el => {
        el.addEventListener('input', () => syncBlockFromDom(block.id));
    });

    return wrap;
}

function syncBlockFromDom(id) {
    const el = canvas.querySelector(`[data-id="${id}"]`);
    if (!el) return;
    const idx = blocks.findIndex(b => b.id === id);
    if (idx < 0) return;
    const b = { ...blocks[idx] };
    el.querySelectorAll('[data-field]').forEach(field => {
        const key = field.dataset.field;
        if (key === 'headers') {
            b.headers = field.value.split(',').map(s => s.trim()).filter(Boolean);
        } else if (key === 'rows') {
            b.rows = field.value.split('\n').filter(Boolean).map(line => line.split(',').map(c => c.trim()));
        } else {
            b[key] = field.value;
        }
    });
    blocks[idx] = b;
}

function renderAll() {
    canvas.innerHTML = '';
    emptyHint.style.display = blocks.length ? 'none' : 'block';
    blocks.forEach(b => canvas.appendChild(renderBlockEditor(b)));
}

document.querySelectorAll('.add-block').forEach(btn => {
    btn.addEventListener('click', () => {
        blocks.push(defaultBlock(btn.dataset.type));
        renderAll();
    });
});

document.getElementById('pageForm').addEventListener('submit', (e) => {
    canvas.querySelectorAll('[data-id]').forEach(w => syncBlockFromDom(w.dataset.id));
    document.getElementById('layoutJson').value = JSON.stringify({ blocks });
});

renderAll();
new Sortable(canvas, { animation: 150, handle: '.badge', onEnd() {
    const order = [...canvas.querySelectorAll('[data-id]')].map(el => el.dataset.id);
    blocks = order.map(id => blocks.find(b => b.id === id)).filter(Boolean);
}});
</script>
@endpush
