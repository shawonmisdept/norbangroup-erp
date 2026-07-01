<?php

return [

    'providers' => [
        'log' => [
            'label'       => 'Log (Development)',
            'description' => 'Writes WhatsApp payloads to Laravel log — no real delivery.',
            'fields'      => [],
        ],
        'meta_cloud' => [
            'label'       => 'Meta WhatsApp Cloud API',
            'description' => 'Official Meta Graph API — requires Phone Number ID and permanent access token from Meta Business Manager.',
            'fields'      => ['api_token', 'phone_number_id'],
            'api_version' => 'v21.0',
        ],
        'sslwireless' => [
            'label'       => 'SSL Wireless WhatsApp',
            'description' => 'Bangladesh WhatsApp Business API via SSL Wireless. Confirm endpoint and payload with your SSL account manager.',
            'fields'      => ['api_token', 'sender_id'],
            'endpoint'    => env('WHATSAPP_SSLWIRELESS_URL', 'https://sms.sslwireless.com/whatsapp/api/send'),
        ],
        'greenweb' => [
            'label'       => 'GreenWeb WhatsApp',
            'description' => 'GreenWeb Bangladesh WhatsApp gateway. Confirm endpoint with GreenWeb support.',
            'fields'      => ['api_token'],
            'endpoint'    => env('WHATSAPP_GREENWEB_URL', 'https://api.greenweb.com.bd/whatsapp.php'),
        ],
        'bulksmsbd' => [
            'label'       => 'BulkSMSBD WhatsApp',
            'description' => 'BulkSMSBD.net WhatsApp API. Confirm endpoint and parameters with BulkSMSBD.',
            'fields'      => ['api_token', 'sender_id'],
            'endpoint'    => env('WHATSAPP_BULKSMSBD_URL', 'https://bulksmsbd.net/api/whatsapp/send'),
        ],
        'custom' => [
            'label'       => 'Custom HTTP API',
            'description' => 'Generic JSON POST for any WhatsApp BSP. Sends phone/to, message/text, and optional sender fields.',
            'fields'      => ['api_token', 'sender_id', 'custom_url'],
        ],
    ],

];
