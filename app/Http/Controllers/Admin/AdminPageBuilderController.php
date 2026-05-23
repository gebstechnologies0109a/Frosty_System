<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AdminPageStatus;
use App\Http\Controllers\Controller;
use App\Models\AdminPage;
use App\Services\AdminPageRenderer;
use App\Console\Commands\SyncSystemPagesCommand;
use App\Services\PageBuilderLayoutService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminPageBuilderController extends Controller
{
    public function sync(SyncSystemPagesCommand $sync): RedirectResponse
    {
        $sync->handle();

        return back()->with('success', 'System pages synced. Any missing pages were registered.');
    }

    public function index(PageBuilderLayoutService $layouts): View
    {
        $pages = AdminPage::query()->orderBy('sort_order')->orderBy('title')->get();

        return view('admin.page-builder.workspace', [
            'pages' => $pages->map(fn (AdminPage $p) => [
                'id' => $p->id,
                'name' => $p->title,
                'slug' => $p->slug,
                'description' => $p->description,
                'is_published' => $p->isPublished(),
            ]),
            'components' => collect(config('page-builder.components', []))
                ->map(fn ($meta, $type) => ['type' => $type, 'label' => $meta['label'] ?? $type])
                ->values(),
            'templates' => collect(config('page-builder.templates', []))
                ->map(fn ($meta, $key) => ['key' => $key, 'label' => $meta['label'] ?? $key])
                ->values(),
            'initialPageId' => $pages->first()?->id,
            'routes' => [
                'list' => route('admin.page-builder.pages'),
                'store' => route('admin.page-builder.pages.store'),
                'show' => url('/admin/page-builder/pages'),
                'update' => url('/admin/page-builder/pages'),
                'publish' => url('/admin/page-builder/pages'),
                'destroy' => url('/admin/page-builder/pages'),
                'preview' => url('/admin/page-preview'),
                'manage' => route('admin.page-builder.manage'),
                'dashboard' => route('admin.dashboard'),
            ],
        ]);
    }

    public function manage(): View
    {
        return view('admin.page-builder.index', [
            'pages' => AdminPage::query()->orderBy('sort_order')->orderBy('title')->get(),
            'blockTypes' => AdminPage::BLOCK_TYPES,
            'totalPages' => AdminPage::query()->count(),
        ]);
    }

    public function listPages(): JsonResponse
    {
        $pages = AdminPage::query()->orderBy('title')->get(['id', 'title', 'slug', 'status', 'description']);

        return response()->json([
            'pages' => $pages->map(fn (AdminPage $p) => [
                'id' => $p->id,
                'name' => $p->title,
                'slug' => $p->slug,
                'description' => $p->description,
                'is_published' => $p->isPublished(),
            ]),
        ]);
    }

    public function storePage(Request $request, PageBuilderLayoutService $layouts): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:120', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', 'unique:admin_pages,slug'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        $slug = $validated['slug'] ?? Str::slug($validated['name']);
        $slug = $this->uniqueSlug($slug);

        $page = AdminPage::query()->create([
            'title' => $validated['name'],
            'slug' => $slug,
            'description' => $validated['description'] ?? null,
            'status' => AdminPageStatus::Draft,
            'layout_json' => $layouts->wrapBlocks([]),
            'sort_order' => (int) AdminPage::query()->max('sort_order') + 1,
            'is_system' => false,
        ]);

        return response()->json([
            'message' => 'Page created.',
            'page' => $this->pagePayload($page),
        ], 201);
    }

    public function showPage(AdminPage $page): JsonResponse
    {
        return response()->json(['page' => $this->pagePayload($page)]);
    }

    public function updatePage(Request $request, AdminPage $page, PageBuilderLayoutService $layouts): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'slug' => ['sometimes', 'required', 'string', 'max:120', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('admin_pages', 'slug')->ignore($page->id)],
            'description' => ['nullable', 'string', 'max:2000'],
            'layout' => ['nullable', 'array'],
            'blocks' => ['nullable', 'array'],
        ]);

        $blocks = $validated['layout'] ?? $validated['blocks'] ?? null;
        if ($blocks !== null) {
            $normalized = $layouts->normalizeBlocks(['blocks' => $blocks]);
            $page->layout_json = $layouts->wrapBlocks($normalized);
        }

        if (isset($validated['name'])) {
            $page->title = $validated['name'];
        }
        if (isset($validated['slug']) && ! $page->is_system) {
            $page->slug = Str::slug($validated['slug']);
        }
        if (array_key_exists('description', $validated)) {
            $page->description = $validated['description'];
        }

        $page->save();

        return response()->json([
            'message' => 'Draft saved.',
            'page' => $this->pagePayload($page->fresh()),
        ]);
    }

    public function publishPage(AdminPage $page): JsonResponse
    {
        if ($page->blockCount() === 0) {
            return response()->json(['message' => 'Add at least one component before publishing.'], 422);
        }

        $page->update(['status' => AdminPageStatus::Published]);

        return response()->json([
            'message' => 'Page published.',
            'page' => $this->pagePayload($page->fresh()),
        ]);
    }

    public function applyTemplate(Request $request, AdminPage $page, PageBuilderLayoutService $layouts): JsonResponse
    {
        $validated = $request->validate([
            'template' => ['required', 'string', Rule::in(array_keys(config('page-builder.templates', [])))],
        ]);

        $blocks = $layouts->templateBlocks($validated['template']);
        $page->layout_json = $layouts->wrapBlocks($blocks);
        $page->save();

        return response()->json([
            'message' => 'Template applied.',
            'page' => $this->pagePayload($page->fresh()),
        ]);
    }

    public function destroyPage(AdminPage $page): JsonResponse
    {
        if ($page->is_system) {
            return response()->json(['message' => 'System pages cannot be deleted.'], 403);
        }

        $page->delete();

        return response()->json(['message' => 'Page deleted.']);
    }

    public function create(): View
    {
        return view('admin.page-builder.edit', [
            'page' => new AdminPage([
                'title' => '',
                'slug' => '',
                'status' => AdminPageStatus::Draft,
                'layout_json' => AdminPageRenderer::defaultLayout(),
            ]),
            'isNew' => true,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['sort_order'] = (int) AdminPage::query()->max('sort_order') + 1;
        $data['is_system'] = false;

        $page = AdminPage::query()->create($data);

        return redirect()
            ->route('admin.page-builder.edit', $page)
            ->with('success', 'Page created. Edit the layout, then save to finalize.');
    }

    public function edit(AdminPage $page): View
    {
        if ($page->layout_json === null || ! is_array($page->layout_json)) {
            $page->layout_json = AdminPageRenderer::defaultLayout();
        }

        if (! $page->status) {
            $page->status = AdminPageStatus::Published;
        }

        return view('admin.page-builder.edit', [
            'page' => $page,
            'isNew' => false,
        ]);
    }

    public function update(Request $request, AdminPage $page): RedirectResponse
    {
        $page->update($this->validated($request, $page));

        if ($request->boolean('finish')) {
            return redirect()
                ->route('admin.page-builder.manage')
                ->with('success', 'Page "'.$page->title.'" saved and finalized.');
        }

        return back()->with('success', 'Page saved. Continue editing or use Save & return to list when done.');
    }

    public function duplicate(AdminPage $page): RedirectResponse
    {
        $copy = $page->replicate(['slug', 'route_name', 'path', 'is_system']);
        $copy->title = $page->title.' (Copy)';
        $copy->slug = $this->uniqueSlug($page->slug.'-copy');
        $copy->status = AdminPageStatus::Draft;
        $copy->route_name = null;
        $copy->path = null;
        $copy->is_system = false;
        $copy->sort_order = (int) AdminPage::query()->max('sort_order') + 1;
        $copy->save();

        return redirect()
            ->route('admin.page-builder.edit', $copy)
            ->with('success', 'Page duplicated. Edit and publish when ready.');
    }

    public function toggleStatus(AdminPage $page): RedirectResponse
    {
        $page->update([
            'status' => $page->status === AdminPageStatus::Published
                ? AdminPageStatus::Draft
                : AdminPageStatus::Published,
        ]);

        return back()->with('success', 'Page status updated to '.$page->fresh()->status->label().'.');
    }

    public function reorder(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'order' => ['required', 'array'],
            'order.*' => ['integer', 'exists:admin_pages,id'],
        ]);

        foreach ($validated['order'] as $position => $pageId) {
            AdminPage::query()->whereKey($pageId)->update(['sort_order' => $position + 1]);
        }

        return back()->with('success', 'Page order updated.');
    }

    public function destroy(AdminPage $page): RedirectResponse
    {
        if ($page->is_system) {
            return back()->withErrors(['delete' => 'System pages cannot be deleted. Unpublish or edit the layout instead.']);
        }

        $page->delete();

        return redirect()
            ->route('admin.page-builder.manage')
            ->with('success', 'Page deleted.');
    }

    public function preview(AdminPage $page, AdminPageRenderer $renderer): View
    {
        return view('admin.page-builder.preview', [
            'page' => $page,
            'blocks' => $page->blocks(),
            'html' => $renderer->render($page),
        ]);
    }

    private function pagePayload(AdminPage $page): array
    {
        return [
            'id' => $page->id,
            'name' => $page->title,
            'slug' => $page->slug,
            'description' => $page->description,
            'is_published' => $page->isPublished(),
            'layout' => $page->blocks(),
        ];
    }

    private function uniqueSlug(string $base): string
    {
        $slug = Str::slug($base);
        $original = $slug;
        $n = 1;

        while (AdminPage::query()->where('slug', $slug)->exists()) {
            $slug = $original.'-'.$n++;
        }

        return $slug;
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, ?AdminPage $existing = null): array
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:120', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', 'unique:admin_pages,slug,'.($existing?->id ?? 'NULL')],
            'status' => ['required', Rule::enum(AdminPageStatus::class)],
            'layout_json' => ['nullable', 'json'],
            'route_name' => ['nullable', 'string', 'max:120'],
            'path' => ['nullable', 'string', 'max:255'],
        ]);

        $layout = ['blocks' => []];
        if (! empty($validated['layout_json'])) {
            $decoded = json_decode($validated['layout_json'], true);
            if (! is_array($decoded)) {
                abort(422, 'Invalid layout JSON.');
            }
            $layout = app(PageBuilderLayoutService::class)->wrapBlocks(
                app(PageBuilderLayoutService::class)->normalizeBlocks($decoded),
            );
        } elseif ($existing?->layout_json) {
            $layout = $existing->layout_json;
        } else {
            $layout = AdminPageRenderer::defaultLayout();
        }

        $data = [
            'title' => $validated['title'],
            'slug' => Str::slug($validated['slug']),
            'status' => AdminPageStatus::from($validated['status']),
            'layout_json' => $layout,
        ];

        if (! $existing?->is_system) {
            $data['route_name'] = $validated['route_name'] ?? null;
            $data['path'] = $validated['path'] ?? null;
        }

        return $data;
    }
}
