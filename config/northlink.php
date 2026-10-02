<?php

return [
    'variant' => env('NORTHLINK_VARIANT', 'esteli'),
    'name' => 'Northlink Microsystem',
    'product' => env('NORTHLINK_PRODUCT', 'EsteliPOS'),
    'disabled_modules' => array_values(array_filter(array_map(
        'trim',
        explode(',', env('NORTHLINK_DISABLED_MODULES', env('NORTHLINK_VARIANT') === 'pueblo_nuevo' ? 'reparaciones' : ''))
    ))),
    'location' => 'Estelí, Nicaragua',
    'website' => env('NORTHLINK_WEBSITE', 'https://northlinkni.com'),
    'support_email' => env('NORTHLINK_SUPPORT_EMAIL'),
    'whatsapp' => env('NORTHLINK_WHATSAPP'),
];
