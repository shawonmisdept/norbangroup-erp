<?php

namespace App\Providers;

use App\Services\AppSettingsService;
use App\Services\Sms\SmsGatewayFactory;
use Illuminate\Support\Facades\Blade;
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

        $this->app->singleton(\App\Contracts\WhatsAppGateway::class, function ($app) {
            return $app->make(\App\Services\Whatsapp\WhatsAppGatewayFactory::class)->make();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);

        if (filter_var(env('APP_DEBUG', false), FILTER_VALIDATE_BOOL)) {
            config(['app.debug' => true]);
            ini_set('display_errors', '1');
            ini_set('display_startup_errors', '1');
            error_reporting(E_ALL);
        }

        if ($this->app->environment('production') && str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }

        $this->app->make(AppSettingsService::class)->applyRuntimeConfig();

        Blade::directive('portalTime', fn (string $expression) => "<?php echo e(\\App\\Support\\PortalDateTime::time($expression)); ?>");
        Blade::directive('portalDateTime', fn (string $expression) => "<?php echo e(\\App\\Support\\PortalDateTime::dateTime($expression)); ?>");
        Blade::directive('portalDateTimeShort', fn (string $expression) => "<?php echo e(\\App\\Support\\PortalDateTime::dateTimeShort($expression)); ?>");
        Blade::directive('portalDateCommaTime', fn (string $expression) => "<?php echo e(\\App\\Support\\PortalDateTime::dateCommaTime($expression)); ?>");
        Blade::directive('portalDateTimeSeconds', fn (string $expression) => "<?php echo e(\\App\\Support\\PortalDateTime::dateTimeWithSeconds($expression)); ?>");
        Blade::directive('portalQuantity', fn (string $expression) => "<?php echo e(\\App\\Support\\PortalNumber::quantity($expression)); ?>");
    }
}
