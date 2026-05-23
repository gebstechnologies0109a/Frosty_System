(function () {
    const cfg = window.PageBuilderWorkspace;
    if (!cfg) return;

    let layout = [];
    let selectedPageId = null;
    let selectedComponentId = null;
    let dirty = false;

    const el = (id) => document.getElementById(id);
    const canvas = el('pb-canvas');
    const canvasEmpty = el('pb-canvas-empty');
    const propsPanel = el('pb-properties');
    const pageSelect = el('pb-page-select');

    function toast(message, type = 'success') {
        const wrap = el('pb-toast');
        const div = document.createElement('div');
        div.className = `alert alert-${type} alert-dismissible fade show shadow-sm`;
        div.innerHTML = `${message}<button type="button" class="btn-close" data-bs-dismiss="alert"></button>`;
        wrap.appendChild(div);
        setTimeout(() => div.remove(), 4000);
    }

    function headers(json = true) {
        const h = { 'X-CSRF-TOKEN': cfg.csrf, Accept: 'application/json' };
        if (json) h['Content-Type'] = 'application/json';
        return h;
    }

    async function api(url, options = {}) {
        const res = await fetch(url, { credentials: 'same-origin', ...options });
        const data = await res.json().catch(() => ({}));
        if (!res.ok) throw new Error(data.message || 'Request failed');
        return data;
    }

    function uid() {
        return 'c-' + Date.now().toString(36) + Math.random().toString(36).slice(2, 7);
    }

    function newBlock(type) {
        return {
            id: uid(),
            type,
            props: JSON.parse(JSON.stringify(cfg.componentDefaults[type] || {})),
        };
    }

    function selectedBlock() {
        return layout.find((b) => b.id === selectedComponentId) || null;
    }

    function renderCanvas() {
        canvas.innerHTML = '';
        canvasEmpty.style.display = layout.length ? 'none' : 'block';

        layout.forEach((block, index) => {
            const wrap = document.createElement('div');
            wrap.className = 'pb-canvas-item' + (block.id === selectedComponentId ? ' is-selected' : '');
            wrap.dataset.id = block.id;

            const controls = document.createElement('div');
            controls.className = 'd-flex flex-wrap gap-1 mb-2';
            controls.innerHTML = `
                <span class="badge text-bg-secondary">${block.type}</span>
                <button type="button" class="btn btn-sm btn-outline-secondary pb-up" ${index === 0 ? 'disabled' : ''}>↑</button>
                <button type="button" class="btn btn-sm btn-outline-secondary pb-down" ${index === layout.length - 1 ? 'disabled' : ''}>↓</button>
                <button type="button" class="btn btn-sm btn-outline-primary pb-edit">Edit</button>
                <button type="button" class="btn btn-sm btn-outline-danger pb-remove">Delete</button>
            `;

            const preview = document.createElement('div');
            preview.className = 'pb-preview-wrap small';
            preview.innerHTML = previewHtml(block);

            wrap.appendChild(controls);
            wrap.appendChild(preview);
            canvas.appendChild(wrap);

            controls.querySelector('.pb-up').addEventListener('click', () => moveBlock(block.id, -1));
            controls.querySelector('.pb-down').addEventListener('click', () => moveBlock(block.id, 1));
            controls.querySelector('.pb-edit').addEventListener('click', () => selectComponent(block.id));
            controls.querySelector('.pb-remove').addEventListener('click', () => removeBlock(block.id));
            wrap.addEventListener('click', (e) => {
                if (!e.target.closest('button')) selectComponent(block.id);
            });
        });
    }

    function previewHtml(block) {
        const p = block.props || {};
        switch (block.type) {
            case 'metric_card':
                return `<strong>${esc(p.title)}</strong>: ${esc(p.value)}<br><span class="text-muted">${esc(p.subtext)}</span>`;
            case 'chart_block':
                return `<strong>${esc(p.title)}</strong> (${esc(p.chart_type)} chart)`;
            case 'hero_block':
                return `<strong>${esc(p.heading)}</strong><br>${esc(p.subheading)}`;
            case 'text_block':
                return `<strong>${esc(p.heading)}</strong><br>${esc(p.body)}`;
            case 'section':
                return `<strong>${esc(p.title)}</strong><br>${esc(p.body)}`;
            case 'system_health_panel':
                return 'System health checks';
            case 'activity_feed':
                return `Activity: ${esc(p.source)} (${p.limit || 5})`;
            case 'divider':
                return '<hr class="my-2">';
            case 'spacer':
                return `<div style="height:${parseInt(p.height, 10) || 16}px;background:#f8f9fa"></div>`;
            default:
                return block.type;
        }
    }

    function esc(s) {
        return String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    }

    function moveBlock(id, dir) {
        const i = layout.findIndex((b) => b.id === id);
        const j = i + dir;
        if (i < 0 || j < 0 || j >= layout.length) return;
        const tmp = layout[i];
        layout[i] = layout[j];
        layout[j] = tmp;
        dirty = true;
        renderCanvas();
        renderProperties();
    }

    function removeBlock(id) {
        layout = layout.filter((b) => b.id !== id);
        if (selectedComponentId === id) selectedComponentId = null;
        dirty = true;
        renderCanvas();
        renderProperties();
    }

    function selectComponent(id) {
        selectedComponentId = id;
        renderCanvas();
        renderProperties();
    }

    function field(label, key, value, type = 'text') {
        const g = document.createElement('div');
        g.className = 'mb-2';
        const id = 'pb-prop-' + key;
        if (type === 'textarea') {
            g.innerHTML = `<label class="form-label small" for="${id}">${label}</label><textarea class="form-control form-control-sm" id="${id}" data-key="${key}" rows="3">${esc(value)}</textarea>`;
        } else if (type === 'select') {
            g.innerHTML = `<label class="form-label small" for="${id}">${label}</label>`;
            const sel = document.createElement('select');
            sel.className = 'form-select form-select-sm';
            sel.id = id;
            sel.dataset.key = key;
            g.appendChild(sel);
            return { group: g, select: sel };
        } else {
            g.innerHTML = `<label class="form-label small" for="${id}">${label}</label><input type="${type}" class="form-control form-control-sm" id="${id}" data-key="${value}" value="${esc(value)}">`;
        }
        return g;
    }

    function renderProperties() {
        const block = selectedBlock();
        propsPanel.innerHTML = '';
        if (!block) {
            propsPanel.innerHTML = '<p class="text-muted small mb-0">Select a component to edit its properties.</p>';
            return;
        }

        const form = document.createElement('div');
        const bind = (key, elInput) => {
            elInput.dataset.key = key;
            elInput.addEventListener('input', () => {
                block.props[key] = elInput.value;
                dirty = true;
                renderCanvas();
            });
        };

        const addInput = (label, key, val, type = 'text') => {
            const g = document.createElement('div');
            g.className = 'mb-2';
            g.innerHTML = `<label class="form-label small">${label}</label>`;
            const input = type === 'textarea'
                ? Object.assign(document.createElement('textarea'), { className: 'form-control form-control-sm', rows: 3 })
                : Object.assign(document.createElement('input'), { type, className: 'form-control form-control-sm' });
            input.value = val ?? '';
            bind(key, input);
            g.appendChild(input);
            form.appendChild(g);
        };

        const p = block.props;
        switch (block.type) {
            case 'metric_card':
                addInput('Title', 'title', p.title);
                addInput('Value', 'value', p.value);
                addInput('Subtext', 'subtext', p.subtext);
                addInput('Icon', 'icon', p.icon);
                addInput('Status', 'status', p.status);
                break;
            case 'chart_block':
                addInput('Chart type', 'chart_type', p.chart_type);
                addInput('Title', 'title', p.title);
                addInput('Labels (CSV)', 'labels', p.labels);
                addInput('Dataset source', 'dataset_source', p.dataset_source);
                break;
            case 'system_health_panel':
                ['api', 'db', 'queue', 'cron', 'ssl'].forEach((k) => {
                    const g = document.createElement('div');
                    g.className = 'form-check';
                    g.innerHTML = `<input class="form-check-input" type="checkbox" id="chk-${k}" ${p.checks?.[k] ? 'checked' : ''}>
                        <label class="form-check-label small" for="chk-${k}">${k.toUpperCase()}</label>`;
                    g.querySelector('input').addEventListener('change', (e) => {
                        p.checks = p.checks || {};
                        p.checks[k] = e.target.checked;
                        dirty = true;
                        renderCanvas();
                    });
                    form.appendChild(g);
                });
                break;
            case 'activity_feed':
                addInput('Source', 'source', p.source);
                addInput('Limit', 'limit', p.limit, 'number');
                break;
            case 'text_block':
                addInput('Heading', 'heading', p.heading);
                addInput('Body', 'body', p.body, 'textarea');
                break;
            case 'hero_block':
                addInput('Heading', 'heading', p.heading);
                addInput('Subheading', 'subheading', p.subheading);
                addInput('Button label', 'button_label', p.button_label);
                addInput('Button link', 'button_link', p.button_link);
                break;
            case 'section':
                addInput('Title', 'title', p.title);
                addInput('Body', 'body', p.body, 'textarea');
                break;
            case 'divider':
                addInput('Style', 'style', p.style);
                break;
            case 'spacer':
                addInput('Height (px)', 'height', p.height, 'number');
                break;
            default:
                form.innerHTML = '<p class="small text-muted">No properties for this type.</p>';
        }

        propsPanel.appendChild(form);
    }

    async function loadPage(id) {
        if (!id) {
            selectedPageId = null;
            layout = [];
            selectedComponentId = null;
            renderCanvas();
            renderProperties();
            return;
        }
        const data = await api(`${cfg.routes.show}/${id}`);
        const page = data.page;
        selectedPageId = page.id;
        layout = page.layout || [];
        selectedComponentId = null;
        el('pb-name').value = page.name || '';
        el('pb-slug').value = page.slug || '';
        el('pb-description').value = page.description || '';
        dirty = false;
        renderCanvas();
        renderProperties();
    }

    async function saveDraft() {
        if (!selectedPageId) {
            toast('Create or select a page first.', 'warning');
            return;
        }
        const data = await api(`${cfg.routes.update}/${selectedPageId}`, {
            method: 'PUT',
            headers: headers(),
            body: JSON.stringify({
                name: el('pb-name').value,
                slug: el('pb-slug').value,
                description: el('pb-description').value,
                layout,
            }),
        });
        dirty = false;
        toast(data.message || 'Draft saved.');
        await refreshPageList(selectedPageId);
    }

    async function publishPage() {
        if (!selectedPageId) return toast('Select a page first.', 'warning');
        if (dirty) await saveDraft();
        const data = await api(`${cfg.routes.publish}/${selectedPageId}/publish`, {
            method: 'POST',
            headers: headers(false),
        });
        toast(data.message || 'Published.');
        await refreshPageList(selectedPageId);
    }

    async function createPage() {
        const name = prompt('Page name');
        if (!name) return;
        const data = await api(cfg.routes.store, {
            method: 'POST',
            headers: headers(),
            body: JSON.stringify({ name, description: '' }),
        });
        await refreshPageList(data.page.id);
        pageSelect.value = String(data.page.id);
        await loadPage(data.page.id);
        toast('Page created.');
    }

    async function deletePage() {
        if (!selectedPageId) return;
        if (!confirm('Delete this page?')) return;
        await api(`${cfg.routes.destroy}/${selectedPageId}`, { method: 'DELETE', headers: headers(false) });
        toast('Page deleted.');
        selectedPageId = null;
        layout = [];
        await refreshPageList();
        await loadPage(pageSelect.value || null);
    }

    async function applyTemplate(key) {
        if (!selectedPageId) return toast('Select a page first.', 'warning');
        if (layout.length && !confirm('Replace current layout with this template?')) return;
        const data = await api(`${cfg.routes.update}/${selectedPageId}/template`, {
            method: 'POST',
            headers: headers(),
            body: JSON.stringify({ template: key }),
        });
        layout = data.page.layout || [];
        dirty = false;
        renderCanvas();
        renderProperties();
        toast('Template applied.');
    }

    async function refreshPageList(selectId = null) {
        const data = await api(cfg.routes.list);
        const current = selectId || pageSelect.value;
        pageSelect.innerHTML = '<option value="">— Choose page —</option>';
        data.pages.forEach((p) => {
            const opt = document.createElement('option');
            opt.value = p.id;
            opt.textContent = `${p.name} (${p.slug})${p.is_published ? ' ✓' : ''}`;
            pageSelect.appendChild(opt);
        });
        if (current) pageSelect.value = String(current);
    }

    document.querySelectorAll('.pb-add-component').forEach((btn) => {
        btn.addEventListener('click', () => {
            if (!selectedPageId) return toast('Create or select a page first.', 'warning');
            const block = newBlock(btn.dataset.type);
            layout.push(block);
            selectedComponentId = block.id;
            dirty = true;
            renderCanvas();
            renderProperties();
        });
    });

    document.querySelectorAll('.pb-apply-template').forEach((btn) => {
        btn.addEventListener('click', () => applyTemplate(btn.dataset.template));
    });

    el('pb-new').addEventListener('click', createPage);
    el('pb-save').addEventListener('click', () => saveDraft().catch((e) => toast(e.message, 'danger')));
    el('pb-publish').addEventListener('click', () => publishPage().catch((e) => toast(e.message, 'danger')));
    el('pb-preview').addEventListener('click', () => {
        if (!selectedPageId) return toast('Select a page first.', 'warning');
        window.open(`${cfg.routes.preview}/${selectedPageId}`, '_blank');
    });
    el('pb-delete').addEventListener('click', () => deletePage().catch((e) => toast(e.message, 'danger')));
    pageSelect.addEventListener('change', () => loadPage(pageSelect.value || null).catch((e) => toast(e.message, 'danger')));

    if (pageSelect.value) {
        loadPage(pageSelect.value).catch(() => {});
    }
})();
