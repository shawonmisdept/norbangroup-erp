<?php

namespace App\Http\Requests;

use App\Models\AppSetting;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAppSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('settings.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'app_name'                     => ['required', 'string', 'max:255'],
            'app_tagline'                  => ['nullable', 'string', 'max:255'],
            'navbar_logo'                  => ['nullable', 'file', 'mimes:jpeg,jpg,png,webp,svg', 'max:2048'],
            'frontend_logo'                => ['nullable', 'file', 'mimes:jpeg,jpg,png,webp,svg', 'max:2048'],
            'remove_navbar_logo'           => ['sometimes', 'boolean'],
            'remove_frontend_logo'         => ['sometimes', 'boolean'],
            'timezone'                     => ['required', 'string', Rule::in(timezone_identifiers_list())],
            'currency_code'                => ['required', 'string', 'max:10'],
            'currency_symbol'              => ['required', 'string', 'max:10'],
            'mail_mailer'                  => ['required', Rule::in(['log', 'gmail', 'smtp', 'sendmail', 'array'])],
            'mail_host'                    => ['nullable', 'string', 'max:255'],
            'mail_port'                    => ['nullable', 'integer', 'min:1', 'max:65535'],
            'mail_username'                => ['nullable', 'string', 'max:255'],
            'mail_password'                => ['nullable', 'string', 'max:255'],
            'mail_encryption'              => ['nullable', Rule::in(['tls', 'ssl', ''])],
            'mail_from_address'            => ['nullable', 'email', 'max:255'],
            'mail_from_name'               => ['nullable', 'string', 'max:255'],
            'mail_admin_address'           => ['nullable', 'email', 'max:255'],
            'notify_popup_enabled'         => ['sometimes', 'boolean'],
            'notify_popup_admin_on_order'  => ['sometimes', 'boolean'],
            'notify_popup_admin_on_status' => ['sometimes', 'boolean'],
            'notify_mail_client_on_order'  => ['sometimes', 'boolean'],
            'notify_mail_admin_on_order'   => ['sometimes', 'boolean'],
            'notify_mail_client_on_status' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->input('mail_mailer') === 'gmail') {
            $this->merge([
                'mail_host'       => 'smtp.gmail.com',
                'mail_port'       => $this->input('mail_port') ?: 587,
                'mail_encryption' => $this->input('mail_encryption') ?: 'tls',
            ]);
        }

        $this->merge([
            'remove_navbar_logo'           => $this->boolean('remove_navbar_logo'),
            'remove_frontend_logo'         => $this->boolean('remove_frontend_logo'),
            'notify_popup_enabled'         => $this->boolean('notify_popup_enabled'),
            'notify_popup_admin_on_order'  => $this->boolean('notify_popup_admin_on_order'),
            'notify_popup_admin_on_status' => $this->boolean('notify_popup_admin_on_status'),
            'notify_mail_client_on_order'  => $this->boolean('notify_mail_client_on_order'),
            'notify_mail_admin_on_order'   => $this->boolean('notify_mail_admin_on_order'),
            'notify_mail_client_on_status' => $this->boolean('notify_mail_client_on_status'),
        ]);
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->input('mail_mailer') !== 'gmail') {
                return;
            }

            if (! $this->filled('mail_username')) {
                $validator->errors()->add('mail_username', 'Gmail address is required.');
            }

            $settings = AppSetting::current();
            $hasPassword = $this->filled('mail_password') || $settings->mailPasswordPlain();

            if (! $hasPassword) {
                $validator->errors()->add('mail_password', 'Gmail App Password is required.');
            }
        });
    }
}
