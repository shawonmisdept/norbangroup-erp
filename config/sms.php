<?php

return [

    'providers' => [
        'log' => [
            'label'       => 'Log (Development)',
            'description' => 'Writes SMS to Laravel log — no real delivery.',
            'fields'      => [],
        ],
        'sslwireless' => [
            'label'       => 'SSL Wireless',
            'description' => 'Bangladesh bulk SMS via SSL Wireless Push API.',
            'fields'      => ['api_key', 'sender_id'],
            'endpoint'    => 'https://sms.sslwireless.com/pushapi/dynamic/server.php',
        ],
        'bulksmsbd' => [
            'label'       => 'BulkSMSBD',
            'description' => 'BulkSMSBD.net SMS API.',
            'fields'      => ['api_key', 'sender_id'],
            'endpoint'    => 'https://bulksmsbd.net/api/smsapi',
        ],
        'greenweb' => [
            'label'       => 'GreenWeb SMS',
            'description' => 'GreenWeb Bangladesh SMS gateway.',
            'fields'      => ['api_key'],
            'endpoint'    => 'https://api.greenweb.com.bd/api.php',
        ],
        'custom' => [
            'label'       => 'Custom HTTP API',
            'description' => 'Generic JSON POST: phone, message, sender.',
            'fields'      => ['api_key', 'sender_id', 'custom_url'],
        ],
    ],

];
