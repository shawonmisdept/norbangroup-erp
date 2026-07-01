<?php

namespace App\Services\Whatsapp;

use App\Contracts\WhatsAppGateway;
use App\Models\AppSetting;

class WhatsAppGatewayFactory
{
    public function make(?AppSetting $settings = null): WhatsAppGateway
    {
        $settings ??= AppSetting::current();
        $provider = $settings->whatsapp_provider ?: 'log';

        return match ($provider) {
            'meta_cloud' => new MetaCloudWhatsAppGateway(
                $settings->whatsappApiTokenPlain(),
                $settings->whatsapp_phone_number_id,
            ),
            'sslwireless' => new SslWirelessWhatsAppGateway(
                $settings->whatsappApiTokenPlain(),
                $settings->whatsapp_sender_id ?: $settings->whatsapp_phone_number_id,
            ),
            'greenweb' => new GreenWebWhatsAppGateway(
                $settings->whatsappApiTokenPlain(),
            ),
            'bulksmsbd' => new BulkSmsBdWhatsAppGateway(
                $settings->whatsappApiTokenPlain(),
                $settings->whatsapp_sender_id ?: $settings->whatsapp_phone_number_id,
            ),
            'custom' => new HttpWhatsAppGateway(
                $settings->whatsapp_custom_url,
                $settings->whatsappApiTokenPlain(),
                $settings->whatsapp_sender_id ?: $settings->whatsapp_phone_number_id,
            ),
            default => new LogWhatsAppGateway(),
        };
    }
}
