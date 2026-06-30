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

    'careers_nav' => [
        ['label' => 'HOME', 'url' => 'https://norbangroup.com/'],
        [
            'label' => 'ABOUT',
            'children' => [
                ['label' => 'About Us', 'url' => 'https://norbangroup.com/about-us/'],
                ['label' => 'Mission Vision Value', 'url' => 'https://norbangroup.com/mission-vision-value/'],
                ['label' => 'Sustainability', 'url' => 'https://norbangroup.com/sustainability/'],
                ['label' => 'Certifications', 'url' => 'https://norbangroup.com/achievements-certifications/'],
                ['label' => 'Working Environment', 'url' => 'https://norbangroup.com/working-environment/'],
            ],
        ],
        [
            'label' => 'COMPANY',
            'children' => [
                ['label' => 'Norban Comtex Ltd', 'url' => 'https://norbangroup.com/norban-comtex-ltd/'],
                ['label' => 'Hornbill Apparel Ltd', 'url' => 'https://norbangroup.com/hornbill-apparel-ltd/'],
                ['label' => 'Filvert Tex Ltd', 'url' => 'https://filverttex.com/'],
            ],
        ],
        [
            'label' => 'SECTION',
            'children' => [
                ['label' => 'Knitting Section', 'url' => 'https://norbangroup.com/knitting/'],
                ['label' => 'Dyeing Section', 'url' => 'https://norbangroup.com/dyeing/'],
                ['label' => 'Cutting Section', 'url' => 'https://norbangroup.com/cutting/'],
                ['label' => 'Printing Section', 'url' => 'https://norbangroup.com/printing/'],
                ['label' => 'Embroidery Section', 'url' => 'https://norbangroup.com/embroidery/'],
                ['label' => 'Finishing Section', 'url' => 'https://norbangroup.com/finishing/'],
                ['label' => 'Quality Section', 'url' => 'https://norbangroup.com/quality/'],
            ],
        ],
        ['label' => 'PRODUCT', 'url' => 'https://norbangroup.com/product/'],
        [
            'label' => 'MEDIA',
            'children' => [
                ['label' => 'Photo Gallery', 'url' => 'https://norbangroup.com/photo-gallery/'],
            ],
        ],
        [
            'label' => 'ORDER NOW',
            'route' => 'orders.create',
            'highlight' => true,
            'external' => false,
        ],
    ],

];
