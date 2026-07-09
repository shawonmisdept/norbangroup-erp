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
            'notify_popup_admin_on_assignment' => ['sometimes', 'boolean'],
            'notify_mail_client_on_order'  => ['sometimes', 'boolean'],
            'notify_mail_admin_on_order'   => ['sometimes', 'boolean'],
            'notify_mail_client_on_status' => ['sometimes', 'boolean'],
            'notify_popup_hrm_late_acceptance' => ['sometimes', 'boolean'],
            'notify_popup_hrm_unmapped_punch'  => ['sometimes', 'boolean'],
            'notify_popup_hrm_manual_punch'    => ['sometimes', 'boolean'],
            'notify_popup_hrm_leave'           => ['sometimes', 'boolean'],
            'notify_mail_hrm_leave'            => ['sometimes', 'boolean'],
            'notify_popup_hrm_sync_failed'     => ['sometimes', 'boolean'],
            'notify_popup_hrm_daily_attendance'=> ['sometimes', 'boolean'],
            'notify_popup_hrm_contract_expiry' => ['sometimes', 'boolean'],
            'notify_popup_hrm_probation_end'   => ['sometimes', 'boolean'],
            'notify_popup_hrm_ot_limit'        => ['sometimes', 'boolean'],
            'notify_popup_hrm_working_hours'   => ['sometimes', 'boolean'],
            'notify_mail_hrm_payslip'          => ['sometimes', 'boolean'],
            'notify_popup_hrm_recruitment'     => ['sometimes', 'boolean'],
            'notify_mail_hrm_recruitment_candidate' => ['sometimes', 'boolean'],
            'notify_sms_hrm_recruitment'       => ['sometimes', 'boolean'],
            'notify_popup_hrm_worker_transfer' => ['sometimes', 'boolean'],
            'notify_popup_hrm_gate_pass'       => ['sometimes', 'boolean'],
            'notify_popup_hrm_proxy_punch'     => ['sometimes', 'boolean'],
            'notify_popup_hrm_manpower_variance' => ['sometimes', 'boolean'],
            'notify_popup_hrm_performance'       => ['sometimes', 'boolean'],
            'notify_popup_tms'                   => ['sometimes', 'boolean'],
            'notify_popup_tms_request_submitted' => ['sometimes', 'boolean'],
            'notify_popup_tms_request_approved'=> ['sometimes', 'boolean'],
            'notify_popup_tms_request_rejected'  => ['sometimes', 'boolean'],
            'notify_popup_tms_request_cancelled' => ['sometimes', 'boolean'],
            'notify_popup_tms_trip_started'      => ['sometimes', 'boolean'],
            'notify_popup_tms_trip_completed'    => ['sometimes', 'boolean'],
            'notify_popup_tms_ot_pending'        => ['sometimes', 'boolean'],
            'notify_popup_tms_odometer_reminder' => ['sometimes', 'boolean'],
            'notify_popup_tms_vehicle_paper'     => ['sometimes', 'boolean'],
            'notify_sms_tms'                     => ['sometimes', 'boolean'],
            'notify_whatsapp_tms'                => ['sometimes', 'boolean'],
            'whatsapp_provider'                  => ['required', Rule::in(array_keys(config('whatsapp.providers', [])))],
            'whatsapp_api_token'                 => ['nullable', 'string', 'max:500'],
            'whatsapp_phone_number_id'           => ['nullable', 'string', 'max:64'],
            'whatsapp_business_account_id'       => ['nullable', 'string', 'max:64'],
            'whatsapp_custom_url'                => ['nullable', 'url', 'max:500'],
            'whatsapp_sender_id'                 => ['nullable', 'string', 'max:64'],
            'recruitment_otp_enabled'          => ['sometimes', 'boolean'],
            'sms_provider'                     => ['required', Rule::in(array_keys(config('sms.providers', [])))],
            'sms_api_key'                      => ['nullable', 'string', 'max:500'],
            'sms_api_secret'                   => ['nullable', 'string', 'max:500'],
            'sms_sender_id'                    => ['nullable', 'string', 'max:20'],
            'sms_custom_url'                   => ['nullable', 'url', 'max:500'],
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
            'notify_popup_admin_on_assignment' => $this->boolean('notify_popup_admin_on_assignment'),
            'notify_mail_client_on_order'  => $this->boolean('notify_mail_client_on_order'),
            'notify_mail_admin_on_order'   => $this->boolean('notify_mail_admin_on_order'),
            'notify_mail_client_on_status' => $this->boolean('notify_mail_client_on_status'),
            'notify_popup_hrm_late_acceptance' => $this->boolean('notify_popup_hrm_late_acceptance'),
            'notify_popup_hrm_unmapped_punch'  => $this->boolean('notify_popup_hrm_unmapped_punch'),
            'notify_popup_hrm_manual_punch'    => $this->boolean('notify_popup_hrm_manual_punch'),
            'notify_popup_hrm_leave'           => $this->boolean('notify_popup_hrm_leave'),
            'notify_mail_hrm_leave'            => $this->boolean('notify_mail_hrm_leave'),
            'notify_popup_hrm_sync_failed'     => $this->boolean('notify_popup_hrm_sync_failed'),
            'notify_popup_hrm_daily_attendance'=> $this->boolean('notify_popup_hrm_daily_attendance'),
            'notify_popup_hrm_contract_expiry' => $this->boolean('notify_popup_hrm_contract_expiry'),
            'notify_popup_hrm_probation_end'   => $this->boolean('notify_popup_hrm_probation_end'),
            'notify_popup_hrm_ot_limit'        => $this->boolean('notify_popup_hrm_ot_limit'),
            'notify_popup_hrm_working_hours'   => $this->boolean('notify_popup_hrm_working_hours'),
            'notify_mail_hrm_payslip'          => $this->boolean('notify_mail_hrm_payslip'),
            'notify_popup_hrm_recruitment'     => $this->boolean('notify_popup_hrm_recruitment'),
            'notify_mail_hrm_recruitment_candidate' => $this->boolean('notify_mail_hrm_recruitment_candidate'),
            'notify_sms_hrm_recruitment'       => $this->boolean('notify_sms_hrm_recruitment'),
            'notify_popup_hrm_worker_transfer' => $this->boolean('notify_popup_hrm_worker_transfer'),
            'notify_popup_hrm_gate_pass'       => $this->boolean('notify_popup_hrm_gate_pass'),
            'notify_popup_hrm_proxy_punch'     => $this->boolean('notify_popup_hrm_proxy_punch'),
            'notify_popup_hrm_manpower_variance' => $this->boolean('notify_popup_hrm_manpower_variance'),
            'notify_popup_hrm_performance'       => $this->boolean('notify_popup_hrm_performance'),
            'notify_popup_tms'                   => $this->boolean('notify_popup_tms'),
            'notify_popup_tms_request_submitted' => $this->boolean('notify_popup_tms_request_submitted'),
            'notify_popup_tms_request_approved'=> $this->boolean('notify_popup_tms_request_approved'),
            'notify_popup_tms_request_rejected'  => $this->boolean('notify_popup_tms_request_rejected'),
            'notify_popup_tms_request_cancelled' => $this->boolean('notify_popup_tms_request_cancelled'),
            'notify_popup_tms_trip_started'      => $this->boolean('notify_popup_tms_trip_started'),
            'notify_popup_tms_trip_completed'    => $this->boolean('notify_popup_tms_trip_completed'),
            'notify_popup_tms_ot_pending'        => $this->boolean('notify_popup_tms_ot_pending'),
            'notify_popup_tms_odometer_reminder' => $this->boolean('notify_popup_tms_odometer_reminder'),
            'notify_popup_tms_vehicle_paper'     => $this->boolean('notify_popup_tms_vehicle_paper'),
            'notify_sms_tms'                     => $this->boolean('notify_sms_tms'),
            'notify_whatsapp_tms'                => $this->boolean('notify_whatsapp_tms'),
            'recruitment_otp_enabled'          => $this->boolean('recruitment_otp_enabled'),
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
