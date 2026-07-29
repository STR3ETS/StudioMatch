<?php

return [
    'tagline' => 'Every sound deserves a studio',
    'heading' => 'Find and book the perfect studio',
    'meta_title' => 'Book recording & mix/master studios',
    'meta_description' => 'Find and book the perfect recording or mix/master studio near you. Compare price, equipment and availability and reserve online with StudioMatch.',

    'search' => [
        'where' => 'Where',
        'where_placeholder' => 'City or address',
        'when' => 'When',
        'when_placeholder' => 'Date & time',
        'type' => 'Type',
        'type_placeholder' => 'Type of studio',
        'submit' => 'Search',
        'mobile_placeholder' => 'Find a studio',
        'mobile_hint' => 'Location · date & time · type',
        'close' => 'Close',
    ],

    'cta_book' => 'Book a studio',
    'cta_host' => 'Rent out your studio',

    'why' => [
        'title' => 'Why StudioMatch',
        'subtitle' => 'Booking without hassle, from search to session.',
        'items' => [
            ['icon' => 'fa-magnifying-glass', 'title' => 'Easy to search', 'text' => 'Find the perfect studio based on your needs, budget and location.'],
            ['icon' => 'fa-shield-halved', 'title' => 'Secure payment', 'text' => 'Pay via iDEAL or credit card, secured through Stripe.'],
            ['icon' => 'fa-clock', 'title' => 'Book online 24/7', 'text' => 'See real-time availability and book whenever it suits you.'],
            ['icon' => 'fa-location-dot', 'title' => 'All across the Netherlands', 'text' => 'From Amsterdam to Maastricht, studios throughout the country.'],
        ],
    ],

    'features' => [
        'book' => [
            'label' => 'Booking',
            'title' => 'Find and book in a few taps',
            'text' => 'Filter by location, price, date and equipment and only see times that are actually free. Pay securely via iDEAL or credit card.',
            'bullets' => [
                'Real-time availability, what you see is what you can book',
                'Full price breakdown before you pay',
                'Secure payment through Stripe',
            ],
            'mock' => 'Screenshot: booking flow with price overview',
            'card_title' => 'Slot reserved',
            'card_sub' => '14:59 minutes left',
        ],
        'manage' => [
            'label' => 'Your dashboard',
            'title' => 'Everything sorted in your dashboard',
            'text' => 'After booking you will find everything in one place: the confirmation with address and contact details, your invoices and a reminder 24 hours in advance.',
            'bullets' => [
                'Confirmation with address and contact details',
                'Reminder 24 hours before your session',
                'Invoices and proof of payment always at hand',
            ],
            'mock' => 'Screenshot: artist dashboard with bookings',
            'card_title' => 'Booking confirmed',
            'card_sub' => 'Sat 14:00 – 17:00',
        ],
    ],

    'map' => [
        'title' => 'Studio locations',
        'text' => 'Discover studios across the Netherlands, from big cities to hidden gems.',
        'soon' => 'Interactive map coming soon',
        'cta' => 'View all studios',
    ],

    'host_cta' => [
        'title' => 'Do you own a studio?',
        'text' => 'Rent out your space to artists and increase your occupancy. Signing up is free.',
        'button' => 'Rent out your studio',
    ],

    'studios' => [
        'title' => 'Featured studios',
        'in_city' => 'Studios in :city',
        'per_hour' => '/hour',
        'view_all' => 'All',
        'view_all_sub' => 'View all studios',
        'prev' => 'Previous',
        'next' => 'Next',
    ],
];
