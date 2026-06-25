<?php

namespace App\Providers;

use App\Services\AppSettingsService;
use App\Services\Sms\SmsGatewayFactory;
use Illuminate\Support\Facades\Schema;
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

        $this->app->make(AppSettingsService::class)->applyRuntimeConfig();
    }
}
