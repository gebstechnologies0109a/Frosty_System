<?php

namespace App\Services;

use App\Enums\AdminPageStatus;
use App\Models\AdminPage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

final class AdminPageLayoutService
{
    public function __construct(
        private readonly AdminPageRenderer $renderer,
    ) {}

    public function overlayHtmlForCurrentRoute(): ?string
    {
        if (! auth()->check()) {
            return null;
        }

        if (! Schema::hasTable('admin_pages')) {
            return null;
        }

        $routeName = request()->route()?->getName();

        if (! $routeName || str_starts_with($routeName, 'admin.page-builder')) {
            return null;
        }

        try {
            $page = AdminPage::query()
                ->where('route_name', $routeName)
                ->where('status', AdminPageStatus::Published)
                ->first();

            if (! $page || $page->blockCount() === 0) {
                return null;
            }

            return $this->renderer->render($page, safe: true);
        } catch (Throwable $e) {
            Log::warning('Page builder overlay skipped', [
                'route' => $routeName,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
