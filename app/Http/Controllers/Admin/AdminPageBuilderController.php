<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\AdminPage;
use App\Services\AdminPageRenderer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AdminPageBuilderController extends Controller
{
    public function index(): View
    {
        return view('admin.page-builder.index', [
            'pages' => AdminPage::query()
                ->orderBy('sort_order')
                ->orderBy('title')
                ->get(),
            'blockTypes' => AdminPage::BLOCK_TYPES,
        ]);
    }

    public function create(): View
    {
        return view('admin.page-builder.edit', [
            'page' => new AdminPage([
                'title' => '',
                'slug' => '',
                'layout_json' => AdminPageRenderer::defaultLayout(),
            ]),
            'isNew' => true,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $page = AdminPage::query()->create($data);

        return redirect()
            ->route('admin.page-builder.edit', $page)
            ->with('success', 'Page created.');
    }

    public function edit(AdminPage $page): View
    {
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

    /** @return array<string, mixed> */
    private function validated(Request $request, ?AdminPage $existing = null): array
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:120', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', 'unique:admin_pages,slug,'.($existing?->id ?? 'NULL')],
            'layout_json' => ['required', 'json'],
        ]);

        $layout = json_decode($validated['layout_json'], true);
        if (! is_array($layout) || ! isset($layout['blocks']) || ! is_array($layout['blocks'])) {
            abort(422, 'Invalid layout JSON.');
        }

        return [
            'title' => $validated['title'],
            'slug' => Str::slug($validated['slug']),
            'layout_json' => $layout,
        ];
    }
}
