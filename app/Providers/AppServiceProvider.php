<?php

namespace App\Providers;

use App\Settings\GeneralSettings;
use App\Settings\InventorySettings;
use App\Settings\MailSettings;
use App\Settings\SalesSettings;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register settings classes as singletons
        $this->app->singleton(GeneralSettings::class);
        $this->app->singleton(MailSettings::class);
        $this->app->singleton(SalesSettings::class);
        $this->app->singleton(InventorySettings::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
