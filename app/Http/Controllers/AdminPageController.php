<?php

namespace App\Http\Controllers;

use App\Models\AdminPage;
use App\Services\AdminPageRenderer;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AdminPageController extends Controller
{
    public function show(string $slug, AdminPageRenderer $renderer): View|RedirectResponse
    {
        $page = AdminPage::query()->where('slug', $slug)->firstOrFail();

        if ($page->is_system && $page->canOpenLive()) {
            return redirect($page->liveUrl());
        }

        return view('pages.dynamic', [
            'page' => $page,
            'html' => $renderer->render($page),
        ]);
    }
}
