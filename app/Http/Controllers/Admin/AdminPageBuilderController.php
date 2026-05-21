<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AdminPageStatus;
use App\Http\Controllers\Controller;
use App\Models\AdminPage;
use App\Services\AdminPageRenderer;
use App\Console\Commands\SyncSystemPagesCommand;
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

    public function index(): View
    {
        return view('admin.page-builder.index', [
            'pages' => AdminPage::query()
                ->orderBy('sort_order')
                ->orderBy('title')
                ->get(),
            'blockTypes' => AdminPage::BLOCK_TYPES,
            'totalPages' => AdminPage::query()->count(),
        ]);
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
        if ($page->layout_json === null) {
            $page->layout_json = AdminPageRenderer::defaultLayout();
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
                ->route('admin.page-builder.index')
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
            ->route('admin.page-builder.index')
            ->with('success', 'Page deleted.');
    }

    public function preview(AdminPage $page, AdminPageRenderer $renderer): View
    {
        return view('admin.page-builder.preview', [
            'page' => $page,
            'html' => $renderer->render($page),
        ]);
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
            if (! is_array($decoded) || ! isset($decoded['blocks']) || ! is_array($decoded['blocks'])) {
                abort(422, 'Invalid layout JSON.');
            }
            $layout = $decoded;
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
