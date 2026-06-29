<?php

namespace App\Services;

use App\Models\AppSetting;

class AppSettingsService
{
    private static ?bool $appSettingsTableExists = null;

    public function applyRuntimeConfig(): void
    {
        if (! $this->tableExists()) {
            return;
        }

        $settings = AppSetting::current();

        config([
            'portal.name'           => $settings->app_name,
            'portal.tagline'        => $settings->app_tagline,
            'portal.navbar_logo'    => $settings->navbarLogoUrl(),
            'portal.frontend_logo'  => $settings->frontendLogoUrl(),
            'app.name'              => $settings->app_name,
            'app.timezone'          => $settings->timezone,
            'portal.currency_code'  => $settings->currency_code,
            'portal.currency_symbol'=> $settings->currency_symbol,
            'mail.from.address'     => $settings->mail_from_address ?: config('mail.from.address'),
            'mail.from.name'        => $settings->mail_from_name ?: $settings->app_name,
            'mail.admin_address'    => $settings->adminMailAddress(),
        ]);

        if ($settings->usesSmtpTransport()) {
            $this->applySmtpConfig($settings);
        } else {
            config(['mail.default' => $settings->mail_mailer]);
        }

        date_default_timezone_set($settings->timezone);
    }

    private function applySmtpConfig(AppSetting $settings): void
    {
        $host = $settings->mail_host ?: AppSetting::gmailDefaults()['mail_host'];
        $port = $settings->mail_port ?: AppSetting::gmailDefaults()['mail_port'];
        $encryption = $settings->mail_encryption ?: AppSetting::gmailDefaults()['mail_encryption'];

        if ($settings->mail_mailer === 'gmail') {
            $host = 'smtp.gmail.com';
            $port = $settings->mail_port ?: 587;
            $encryption = $settings->mail_encryption ?: 'tls';
        }

        config([
            'mail.default'               => 'smtp',
            'mail.mailers.smtp.host'     => $host,
            'mail.mailers.smtp.port'     => $port,
            'mail.mailers.smtp.username' => $settings->mail_username,
            'mail.mailers.smtp.password' => $settings->mailPasswordPlain(),
            'mail.mailers.smtp.scheme'   => $encryption === 'ssl' ? 'smtps' : 'smtp',
        ]);
    }

    private function tableExists(): bool
    {
        if (self::$appSettingsTableExists !== null) {
            return self::$appSettingsTableExists;
        }

        try {
            self::$appSettingsTableExists = \Illuminate\Support\Facades\Schema::hasTable('app_settings');
        } catch (\Throwable) {
            self::$appSettingsTableExists = false;
        }

        return self::$appSettingsTableExists;
    }
}
