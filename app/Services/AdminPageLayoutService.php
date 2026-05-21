<?php

namespace App\Services;

use App\Enums\AdminPageStatus;
use App\Models\AdminPage;

final class AdminPageLayoutService
{
    public function __construct(
        private readonly AdminPageRenderer $renderer,
    ) {}

    public function overlayHtmlForCurrentRoute(): ?string
    {
        $routeName = request()->route()?->getName();

        if (! $routeName || str_starts_with($routeName, 'admin.page-builder')) {
            return null;
        }

        $page = AdminPage::query()
            ->where('route_name', $routeName)
            ->where('status', AdminPageStatus::Published)
            ->first();

        if (! $page || $page->blockCount() === 0) {
            return null;
        }

        return $this->renderer->render($page);
    }
}
