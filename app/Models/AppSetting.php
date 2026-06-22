<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;

class AppSetting extends Model
{
    private static ?self $resolved = null;

    protected $fillable = [
        'app_name', 'app_tagline', 'navbar_logo', 'frontend_logo',
        'timezone', 'currency_code', 'currency_symbol',
        'mail_mailer', 'mail_host', 'mail_port', 'mail_username', 'mail_password',
        'mail_encryption', 'mail_from_address', 'mail_from_name', 'mail_admin_address',
        'notify_popup_enabled', 'notify_popup_admin_on_order', 'notify_popup_admin_on_status',
        'notify_mail_client_on_order', 'notify_mail_admin_on_order', 'notify_mail_client_on_status',
    ];

    protected $casts = [
        'mail_port'                       => 'integer',
        'notify_popup_enabled'            => 'boolean',
        'notify_popup_admin_on_order'     => 'boolean',
        'notify_popup_admin_on_status'    => 'boolean',
        'notify_mail_client_on_order'     => 'boolean',
        'notify_mail_admin_on_order'      => 'boolean',
        'notify_mail_client_on_status'    => 'boolean',
    ];

    protected $hidden = ['mail_password'];

    public static function current(): self
    {
        if (static::$resolved !== null) {
            return static::$resolved;
        }

        $id = Cache::rememberForever('app_settings_id', function () {
            return static::query()->firstOrCreate([], static::defaults())->id;
        });

        $settings = static::query()->find($id);

        if (! $settings) {
            Cache::forget('app_settings_id');
            $settings = static::query()->firstOrCreate([], static::defaults());
            Cache::forever('app_settings_id', $settings->id);
        }

        return static::$resolved = $settings;
    }

    public static function defaults(): array
    {
        return [
            'app_name'                     => config('portal.name', 'Norbangroup'),
            'app_tagline'                  => config('portal.tagline', 'Manufacturer'),
            'timezone'                     => config('app.timezone', 'Asia/Dhaka'),
            'currency_code'                => 'BDT',
            'currency_symbol'              => '৳',
            'mail_mailer'                  => config('mail.default', 'log'),
            'mail_host'                    => config('mail.mailers.smtp.host'),
            'mail_port'                    => config('mail.mailers.smtp.port'),
            'mail_username'                => config('mail.mailers.smtp.username'),
            'mail_encryption'              => config('mail.mailers.smtp.scheme') === 'smtps' ? 'tls' : null,
            'mail_from_address'            => config('mail.from.address'),
            'mail_from_name'               => config('mail.from.name'),
            'mail_admin_address'           => config('mail.admin_address'),
            'notify_popup_enabled'         => true,
            'notify_popup_admin_on_order'  => true,
            'notify_popup_admin_on_status' => true,
            'notify_mail_client_on_order'  => true,
            'notify_mail_admin_on_order'   => true,
            'notify_mail_client_on_status' => true,
        ];
    }

    public static function clearCache(): void
    {
        static::$resolved = null;
        Cache::forget('app_settings_id');
        Cache::forget('app_settings');
    }

    public function setMailPasswordAttribute(?string $value): void
    {
        if ($value !== null && $value !== '') {
            $this->attributes['mail_password'] = Crypt::encryptString($value);
        }
    }

    public function mailPasswordPlain(): ?string
    {
        if (! $this->attributes['mail_password'] ?? null) {
            return null;
        }

        try {
            return Crypt::decryptString($this->attributes['mail_password']);
        } catch (\Throwable) {
            return null;
        }
    }

    public function adminMailAddress(): string
    {
        return $this->mail_admin_address ?: config('mail.admin_address');
    }

    public function usesSmtpTransport(): bool
    {
        return in_array($this->mail_mailer, ['smtp', 'gmail'], true);
    }

    public function canSendMail(): bool
    {
        if (in_array($this->mail_mailer, ['log', 'array'], true)) {
            return true;
        }

        if ($this->usesSmtpTransport()) {
            return filled($this->mail_username) && filled($this->mailPasswordPlain());
        }

        return $this->mail_mailer !== null;
    }

    public static function gmailDefaults(): array
    {
        return [
            'mail_host'       => 'smtp.gmail.com',
            'mail_port'       => 587,
            'mail_encryption' => 'tls',
        ];
    }

    public function navbarLogoUrl(): ?string
    {
        return $this->logoUrl($this->navbar_logo);
    }

    public function frontendLogoUrl(): ?string
    {
        return $this->logoUrl($this->frontend_logo);
    }

    private function logoUrl(?string $path): ?string
    {
        return $path ? Storage::disk('public')->url($path) : null;
    }
}
