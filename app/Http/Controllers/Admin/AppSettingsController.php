<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Sms\SmsGatewayFactory;
use App\Http\Requests\UpdateAppSettingsRequest;
use App\Models\AppSetting;
use App\Services\AppSettingLogoService;
use App\Services\AppSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class AppSettingsController extends Controller
{
    public function edit(): View
    {
        $settings = AppSetting::current();
        $timezones = timezone_identifiers_list();
        $currencies = [
            'BDT' => ['symbol' => '৳', 'label' => 'Bangladeshi Taka (BDT)'],
            'USD' => ['symbol' => '$', 'label' => 'US Dollar (USD)'],
            'EUR' => ['symbol' => '€', 'label' => 'Euro (EUR)'],
            'GBP' => ['symbol' => '£', 'label' => 'British Pound (GBP)'],
        ];

        return view('admin.settings.edit', compact('settings', 'timezones', 'currencies'));
    }

    public function update(UpdateAppSettingsRequest $request, AppSettingsService $settingsService): RedirectResponse
    {
        $settings = AppSetting::current();
        $data = $request->validated();

        if (empty($data['mail_password'])) {
            unset($data['mail_password']);
        }

        if (empty($data['sms_api_key'])) {
            unset($data['sms_api_key']);
        }

        if (empty($data['sms_api_secret'])) {
            unset($data['sms_api_secret']);
        }

        if ($data['mail_mailer'] === 'gmail') {
            $data = array_merge($data, AppSetting::gmailDefaults());

            if (empty($data['mail_from_address']) && ! empty($data['mail_username'])) {
                $data['mail_from_address'] = $data['mail_username'];
            }
        }

        if ($request->boolean('remove_navbar_logo')) {
            AppSettingLogoService::delete($settings->navbar_logo);
            $data['navbar_logo'] = null;
        }

        if ($request->boolean('remove_frontend_logo')) {
            AppSettingLogoService::delete($settings->frontend_logo);
            $data['frontend_logo'] = null;
        }

        if ($request->hasFile('navbar_logo')) {
            $data['navbar_logo'] = AppSettingLogoService::store(
                $request->file('navbar_logo'),
                'navbar',
                $settings->navbar_logo
            );
        }

        if ($request->hasFile('frontend_logo')) {
            $data['frontend_logo'] = AppSettingLogoService::store(
                $request->file('frontend_logo'),
                'frontend',
                $settings->frontend_logo
            );
        }

        unset($data['remove_navbar_logo'], $data['remove_frontend_logo']);

        $settings->update($data);
        AppSetting::clearCache();
        $settingsService->applyRuntimeConfig();

        return back()->with('success', 'Application settings saved successfully.');
    }

    public function sendTestMail(Request $request, AppSettingsService $settingsService): RedirectResponse
    {
        abort_unless($request->user()?->hasPermission('settings.manage'), 403);

        $settings = AppSetting::current();

        if (! $settings->usesSmtpTransport()) {
            return back()->with('error', 'Configure Gmail or SMTP mail driver before sending a test email.');
        }

        if (! $settings->canSendMail()) {
            return back()->with('error', 'Mail credentials are incomplete. Save Gmail address and App Password in App Settings first.');
        }

        $settingsService->applyRuntimeConfig();

        try {
            Mail::raw(
                'This is a test email from ' . config('app.name') . '. Your mail settings are working correctly.',
                fn ($message) => $message
                    ->to($request->user()->email)
                    ->subject('Test Email — ' . config('app.name'))
            );
        } catch (\Throwable $e) {
            return back()->with('error', 'Failed to send test email. Check Gmail App Password and settings. ' . $e->getMessage());
        }

        return back()->with('success', 'Test email sent to ' . $request->user()->email . '.');
    }

    public function sendTestSms(Request $request, SmsGatewayFactory $factory): RedirectResponse
    {
        abort_unless($request->user()?->hasPermission('settings.manage'), 403);

        $validated = $request->validate([
            'test_sms_phone' => ['required', 'string', 'max:20'],
        ]);

        $settings = AppSetting::current();

        if (! $settings->canSendSms()) {
            return back()->with('error', 'SMS provider credentials are incomplete. Save API key and sender ID first.');
        }

        $message = 'Test SMS from ' . config('app.name') . '. Your SMS gateway is configured correctly.';

        if (! $factory->make($settings)->send($validated['test_sms_phone'], $message)) {
            return back()->with('error', 'Failed to send test SMS. Check provider credentials and logs.');
        }

        return back()->with('success', 'Test SMS sent to ' . $validated['test_sms_phone'] . '.');
    }
}
