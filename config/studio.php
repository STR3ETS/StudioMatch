<?php

return [
    // Servicekosten voor de artiest, bovenop de huur (klantkeuze bij BESLISSING 2).
    'service_fee_percent' => 9,

    // Btw over de servicekosten (scope §2.6).
    'vat_percent' => 21,

    // Slot-reservering tijdens de checkout (scope §2.5).
    'checkout_hold_minutes' => 15,

    // Vaste apparatuurlijst (scope §2.2) - sleutels vertaald via studios.equipment.*
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
    ],

    // Voorzieningen (scope §2.2) - sleutels vertaald via studios.facilities.*
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

    // DAW's (scope §2.2) - merknamen, niet vertaald
    'daws' => [
        'Logic',
        'Pro Tools',
        'FL Studio',
        'Ableton',
        'Cubase',
    ],
];
