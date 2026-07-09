<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Schema;
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
        'notify_popup_admin_on_assignment',
        'notify_mail_client_on_order', 'notify_mail_admin_on_order', 'notify_mail_client_on_status',
        'notify_popup_hrm_late_acceptance', 'notify_popup_hrm_unmapped_punch', 'notify_popup_hrm_manual_punch',
        'notify_popup_hrm_leave', 'notify_mail_hrm_leave', 'notify_popup_hrm_sync_failed',
        'notify_popup_hrm_daily_attendance', 'notify_popup_hrm_contract_expiry', 'notify_popup_hrm_probation_end',
        'notify_popup_hrm_ot_limit', 'notify_popup_hrm_working_hours', 'notify_mail_hrm_payslip',
        'notify_popup_hrm_recruitment', 'notify_mail_hrm_recruitment_candidate', 'notify_sms_hrm_recruitment',
        'notify_popup_hrm_worker_transfer', 'notify_popup_hrm_gate_pass', 'notify_popup_hrm_proxy_punch',
        'notify_popup_hrm_manpower_variance', 'notify_popup_hrm_performance',
        'notify_popup_tms', 'notify_popup_tms_request_submitted', 'notify_popup_tms_request_approved',
        'notify_popup_tms_request_rejected', 'notify_popup_tms_request_cancelled',
        'notify_popup_tms_trip_started', 'notify_popup_tms_trip_completed',
        'notify_popup_tms_ot_pending', 'notify_popup_tms_odometer_reminder', 'notify_popup_tms_vehicle_paper',
        'notify_sms_tms', 'notify_whatsapp_tms',
        'whatsapp_provider', 'whatsapp_api_token', 'whatsapp_phone_number_id', 'whatsapp_business_account_id',
        'whatsapp_custom_url', 'whatsapp_sender_id',
        'recruitment_otp_enabled',
        'sms_provider', 'sms_api_key', 'sms_api_secret', 'sms_sender_id', 'sms_custom_url',
    ];

    protected $casts = [
        'mail_port'                       => 'integer',
        'notify_popup_enabled'            => 'boolean',
        'notify_popup_admin_on_order'     => 'boolean',
        'notify_popup_admin_on_status'    => 'boolean',
        'notify_popup_admin_on_assignment'=> 'boolean',
        'notify_mail_client_on_order'     => 'boolean',
        'notify_mail_admin_on_order'      => 'boolean',
        'notify_mail_client_on_status'    => 'boolean',
        'notify_popup_hrm_late_acceptance' => 'boolean',
        'notify_popup_hrm_unmapped_punch'  => 'boolean',
        'notify_popup_hrm_manual_punch'    => 'boolean',
        'notify_popup_hrm_leave'           => 'boolean',
        'notify_mail_hrm_leave'            => 'boolean',
        'notify_popup_hrm_sync_failed'     => 'boolean',
        'notify_popup_hrm_daily_attendance'=> 'boolean',
        'notify_popup_hrm_contract_expiry' => 'boolean',
        'notify_popup_hrm_probation_end'   => 'boolean',
        'notify_popup_hrm_ot_limit'        => 'boolean',
        'notify_popup_hrm_working_hours'   => 'boolean',
        'notify_mail_hrm_payslip'          => 'boolean',
        'notify_popup_hrm_recruitment'     => 'boolean',
        'notify_mail_hrm_recruitment_candidate' => 'boolean',
        'notify_sms_hrm_recruitment'       => 'boolean',
        'notify_popup_hrm_worker_transfer' => 'boolean',
        'notify_popup_hrm_gate_pass'       => 'boolean',
        'notify_popup_hrm_proxy_punch'     => 'boolean',
        'notify_popup_hrm_manpower_variance' => 'boolean',
        'notify_popup_hrm_performance'       => 'boolean',
        'notify_popup_tms'                   => 'boolean',
        'notify_popup_tms_request_submitted' => 'boolean',
        'notify_popup_tms_request_approved'=> 'boolean',
        'notify_popup_tms_request_rejected'  => 'boolean',
        'notify_popup_tms_request_cancelled' => 'boolean',
        'notify_popup_tms_trip_started'      => 'boolean',
        'notify_popup_tms_trip_completed'    => 'boolean',
        'notify_popup_tms_ot_pending'        => 'boolean',
        'notify_popup_tms_odometer_reminder' => 'boolean',
        'notify_popup_tms_vehicle_paper'     => 'boolean',
        'notify_sms_tms'                     => 'boolean',
        'notify_whatsapp_tms'                => 'boolean',
        'recruitment_otp_enabled'          => 'boolean',
    ];

    protected $hidden = ['mail_password', 'sms_api_key', 'sms_api_secret', 'whatsapp_api_token'];

    public static function current(): self
    {
        if (static::$resolved !== null) {
            return static::$resolved;
        }

        $id = Cache::rememberForever('app_settings_id', function () {
            return static::query()->firstOrCreate([], static::defaultsForSchema())->id;
        });

        $settings = static::query()->find($id);

        if (! $settings) {
            Cache::forget('app_settings_id');
            $settings = static::query()->firstOrCreate([], static::defaultsForSchema());
            Cache::forever('app_settings_id', $settings->id);
        }

        return static::$resolved = $settings;
    }

    public static function defaults(): array
    {
        return [
            'app_name'                     => config('portal.name', 'Norban Group'),
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
            'notify_popup_admin_on_assignment' => true,
            'notify_mail_client_on_order'  => true,
            'notify_mail_admin_on_order'   => true,
            'notify_mail_client_on_status' => true,
            'notify_popup_hrm_late_acceptance' => true,
            'notify_popup_hrm_unmapped_punch'  => true,
            'notify_popup_hrm_manual_punch'    => false,
            'notify_popup_hrm_leave'           => true,
            'notify_mail_hrm_leave'            => true,
            'notify_popup_hrm_sync_failed'     => true,
            'notify_popup_hrm_daily_attendance'=> true,
            'notify_popup_hrm_contract_expiry' => true,
            'notify_popup_hrm_probation_end'   => true,
            'notify_popup_hrm_ot_limit'        => true,
            'notify_popup_hrm_working_hours'   => true,
            'notify_mail_hrm_payslip'          => true,
            'notify_popup_hrm_recruitment'     => true,
            'notify_mail_hrm_recruitment_candidate' => true,
            'notify_sms_hrm_recruitment'       => false,
            'notify_popup_hrm_worker_transfer' => true,
            'notify_popup_hrm_gate_pass'       => true,
            'notify_popup_hrm_proxy_punch'     => true,
            'notify_popup_hrm_manpower_variance' => true,
            'notify_popup_hrm_performance'       => true,
            'notify_popup_tms'                   => true,
            'notify_popup_tms_request_submitted' => true,
            'notify_popup_tms_request_approved'=> true,
            'notify_popup_tms_request_rejected'=> true,
            'notify_popup_tms_request_cancelled' => true,
            'notify_popup_tms_trip_started'      => true,
            'notify_popup_tms_trip_completed'    => true,
            'notify_popup_tms_ot_pending'        => true,
            'notify_popup_tms_odometer_reminder' => true,
            'notify_popup_tms_vehicle_paper'     => true,
            'notify_sms_tms'                     => false,
            'notify_whatsapp_tms'                => false,
            'whatsapp_provider'                  => 'log',
            'recruitment_otp_enabled'          => true,
            'sms_provider'                     => 'log',
        ];
    }

    /** @return array<string, mixed> */
    public static function defaultsForSchema(): array
    {
        $defaults = static::defaults();

        if (! Schema::hasTable('app_settings')) {
            return $defaults;
        }

        $columns = array_flip(Schema::getColumnListing('app_settings'));

        return array_intersect_key($defaults, $columns);
    }

    public static function clearCache(): void
    {
        static::$resolved = null;
        Cache::forget('app_settings_id');
        Cache::forget('app_settings');
    }

    public function setMailPasswordAttribute(?string $value): void
    {
        if ($value === null || $value === '') {
            $this->attributes['mail_password'] = null;

            return;
        }

        $this->attributes['mail_password'] = Crypt::encryptString($value);
    }

    public function mailPasswordPlain(): ?string
    {
        return $this->decryptAttribute('mail_password');
    }

    public function setSmsApiKeyAttribute(?string $value): void
    {
        $this->encryptAttribute('sms_api_key', $value);
    }

    public function setSmsApiSecretAttribute(?string $value): void
    {
        $this->encryptAttribute('sms_api_secret', $value);
    }

    public function smsApiKeyPlain(): ?string
    {
        return $this->decryptAttribute('sms_api_key');
    }

    public function smsApiSecretPlain(): ?string
    {
        return $this->decryptAttribute('sms_api_secret');
    }

    public function setWhatsappApiTokenAttribute(?string $value): void
    {
        $this->encryptAttribute('whatsapp_api_token', $value);
    }

    public function whatsappApiTokenPlain(): ?string
    {
        return $this->decryptAttribute('whatsapp_api_token');
    }

    public function canSendWhatsApp(): bool
    {
        if ($this->whatsapp_provider === 'log') {
            return true;
        }

        if ($this->whatsapp_provider === 'meta_cloud') {
            return filled($this->whatsappApiTokenPlain()) && filled($this->whatsapp_phone_number_id);
        }

        if ($this->whatsapp_provider === 'greenweb') {
            return filled($this->whatsappApiTokenPlain());
        }

        if ($this->whatsapp_provider === 'custom') {
            return filled($this->whatsapp_custom_url);
        }

        if (in_array($this->whatsapp_provider, ['sslwireless', 'bulksmsbd'], true)) {
            return filled($this->whatsappApiTokenPlain());
        }

        return false;
    }

    public function canSendSms(): bool
    {
        if ($this->sms_provider === 'log') {
            return true;
        }

        if ($this->sms_provider === 'greenweb') {
            return filled($this->smsApiKeyPlain());
        }

        if ($this->sms_provider === 'custom') {
            return filled($this->sms_custom_url);
        }

        return filled($this->smsApiKeyPlain()) && filled($this->sms_sender_id);
    }

    public function recruitmentOtpEnabled(): bool
    {
        return (bool) $this->recruitment_otp_enabled;
    }

    private function encryptAttribute(string $column, ?string $value): void
    {
        if ($value === null || $value === '') {
            $this->attributes[$column] = null;

            return;
        }

        $this->attributes[$column] = Crypt::encryptString($value);
    }

    private function decryptAttribute(string $column): ?string
    {
        if (! ($this->attributes[$column] ?? null)) {
            return null;
        }

        try {
            return Crypt::decryptString($this->attributes[$column]);
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
