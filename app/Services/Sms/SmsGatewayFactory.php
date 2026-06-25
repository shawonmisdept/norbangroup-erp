<?php

namespace App\Services\Sms;

use App\Contracts\SmsGateway;
use App\Models\AppSetting;

class SmsGatewayFactory
{
    public function make(?AppSetting $settings = null): SmsGateway
    {
        $settings ??= AppSetting::current();
        $provider = $settings->sms_provider ?: 'log';

        return match ($provider) {
            'sslwireless' => new SslWirelessSmsGateway(
                $settings->smsApiKeyPlain(),
                $settings->sms_sender_id,
            ),
            'bulksmsbd' => new BulkSmsBdGateway(
                $settings->smsApiKeyPlain(),
                $settings->sms_sender_id,
            ),
            'greenweb' => new GreenWebSmsGateway(
                $settings->smsApiKeyPlain(),
            ),
            'custom' => new HttpSmsGateway(
                $settings->sms_custom_url,
                $settings->smsApiKeyPlain(),
                $settings->sms_sender_id ?: 'NORBAN',
            ),
            default => new LogSmsGateway(),
        };
    }
}
