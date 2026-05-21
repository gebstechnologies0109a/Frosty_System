<?php

namespace App\Http\Controllers;

use App\Models\AdminPage;
use App\Services\AdminPageRenderer;
use Illuminate\View\View;

class AdminPageController extends Controller
{
    public function show(string $slug, AdminPageRenderer $renderer): View
    {
        $page = AdminPage::query()->where('slug', $slug)->firstOrFail();

        return view('pages.dynamic', [
            'page' => $page,
            'html' => $renderer->render($page),
        ]);
    }
}
