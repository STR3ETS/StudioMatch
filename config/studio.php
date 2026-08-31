<?php

return [

    'service_fee_percent' => 9,

    'vat_percent' => 21,

    'checkout_hold_minutes' => 15,

    'booking_max_hours' => 12,

    'booking_horizon_days' => 365,

    'contact_email' => env('CONTACT_EMAIL', 'info@studiomatch.nl'),

    'equipment' => [
        'mic_condenser',
        'mic_dynamic',
        'mic_usb',
        'monitors',
        'midi25',
        'midi49',
        'midi61',
        'midi88',
        'piano',
        'guitar_acoustic',
        'guitar_electric',
        'bass',
        'drums',
        'screen_extra',
        'headphones',
    ],

    'facilities' => [
        'wifi',
        'parking',
        'kitchen',
        'microwave',
        'fridge',
        'coffee',
        'smoking',
        'ac',
    ],

    'daws' => [
        'Logic',
        'Pro Tools',
        'FL Studio',
        'Ableton',
        'Cubase',
    ],
];
