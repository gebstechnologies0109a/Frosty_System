<?php

namespace App\Providers;

use App\Services\AdminPageLayoutService;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        View::composer('layouts.app', function ($view): void {
            $view->with(
                'pageBuilderOverlay',
                app(AdminPageLayoutService::class)->overlayHtmlForCurrentRoute()
            );
        });
    }
}
