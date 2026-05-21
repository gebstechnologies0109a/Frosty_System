<?php

namespace App\Providers;

use App\Services\AdminPageLayoutService;
use Illuminate\Support\Facades\URL;
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
        if ($this->app->environment('production') && str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }

        View::composer('layouts.app', function ($view): void {
            $view->with(
                'pageBuilderOverlay',
                app(AdminPageLayoutService::class)->overlayHtmlForCurrentRoute()
            );
        });
    }
}
