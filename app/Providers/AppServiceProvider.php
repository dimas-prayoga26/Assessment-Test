<?php

namespace App\Providers;

use App\Support\Branding;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('*', function ($view): void {
            $branding = app(Branding::class);

            $view->with([
                'branding' => $branding->current(request()),
                'brandingLogoUrl' => $branding->logoUrl(request()),
            ]);
        });
    }
}
