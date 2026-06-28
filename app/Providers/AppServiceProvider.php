<?php

namespace App\Providers;

use App\Services\AppSettingsService;
use App\Services\Sms\SmsGatewayFactory;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(\App\Contracts\SmsGateway::class, function ($app) {
            return $app->make(SmsGatewayFactory::class)->make();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);

        if ($this->app->environment('production') && str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }

        $this->app->make(AppSettingsService::class)->applyRuntimeConfig();
    }
}
