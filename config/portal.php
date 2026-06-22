<?php

return [

    'name' => env('PORTAL_NAME', 'Norbangroup'),

    'tagline' => env('PORTAL_TAGLINE', 'Manufacturer'),

    'navbar_logo'   => null,
    'frontend_logo' => null,

    'hero_image' => env('PORTAL_HERO_IMAGE', 'https://norbangroup.com/wp-content/uploads/2025/01/slide03.jpg'),

    'admin_email' => env('MAIL_ADMIN_ADDRESS', 'admin@norbangroup.com'),

    'currency_code'   => env('PORTAL_CURRENCY', 'BDT'),
    'currency_symbol' => env('PORTAL_CURRENCY_SYMBOL', '৳'),

    'colors' => [
        'brand' => '#1E3A5F',
        'brand_dark' => '#152D4A',
        'brand_light' => '#2A4F80',
        'gold' => '#C9A84C',
        'gold_light' => '#F5EAC8',
        'gold_dark' => '#A88930',
    ],

];
