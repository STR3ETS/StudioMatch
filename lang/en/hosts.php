<?php

return [
    'meta_title' => 'For studios: list your studio',
    'meta_description' => 'List your recording or mix/master studio on StudioMatch, increase your occupancy and let the platform handle admin and payments. 9% commission, no hidden costs.',

    'hero' => [
        'eyebrow' => 'For studio owners',
        'heading' => 'Fill your studio with bookings',
        'subtitle' => 'Step out of the anonymity of word-of-mouth and Instagram. Put your studio on the map, manage your availability in one place and let StudioMatch handle admin and payments.',
        'cta' => 'List your studio',
        'secondary' => 'How it works',
        'mock' => 'Screenshot: host dashboard with revenue overview',
        'cards' => [
            'booking_title' => 'New request',
            'booking_sub' => 'Sat 14:00 – 17:00 · Recording',
            'payout_title' => 'Payout sent',
            'payout_sub' => '€ 133.66 via Stripe',
        ],
        'stats' => [
            ['value' => '9%', 'label' => 'commission per booking'],
            ['value' => '€ 0', 'label' => 'setup or subscription fees'],
            ['value' => '100%', 'label' => 'your calendar, your rules'],
        ],
    ],

    'statement' => [
        'no' => 'No',
        'lines' => ['hidden costs.', 'exclusivity.', 'double calendar management.'],
        'sub' => "The three biggest worries of studio owners, deliberately removed from the model. What's left: bookings, paid.",
    ],

    'benefits' => [
        'title' => 'Why StudioMatch',
        'subtitle' => 'More bookings, less hassle. We connect you with artists actively looking for studio time.',
        'items' => [
            'exposure' => ['title' => 'More bookings', 'text' => 'Reach a broad group of artists actively searching for studio time and increase your occupancy.'],
            'admin' => ['title' => 'Less admin', 'text' => 'From availability to invoicing: the platform takes the work off your hands so you can focus on recording.'],
            'payout' => ['title' => 'Fast payouts', 'text' => 'Payments run securely through Stripe. You receive your money quickly, without long waits.'],
            'control' => ['title' => 'You stay in control', 'text' => 'You set your rate, your availability and your house rules, and approve every booking yourself.'],
        ],
    ],

    'features' => [
        'title' => 'Everything for the host',
        'subtitle' => 'From your first listing to the payout: StudioMatch gives you the tools to rent out your studio effortlessly.',
        'items' => [
            'dashboard' => [
                'label' => 'Dashboard',
                'title' => 'Your command center',
                'text' => 'Manage your studio, rooms and details from one clear dashboard, on desktop and mobile.',
                'bullets' => [
                    'Add, edit or remove rooms',
                    'Adjust prices and minimum booking duration whenever you want',
                    'Revenue at a glance: gross, commission and net',
                ],
                'mock' => 'Screenshot: host dashboard with revenue overview',
                'card_title' => 'Net revenue',
                'card_sub' => '€ 1,240 this month',
            ],
            'availability' => [
                'label' => 'Availability',
                'title' => 'You decide when you are open',
                'text' => 'Set a weekly schedule, block your own sessions and switch your studio to holiday mode. All in one calendar, no double work.',
                'bullets' => [
                    'Weekly schedule per room',
                    'Blocks for your own sessions or maintenance',
                    'Holiday mode: temporarily hidden',
                ],
                'mock' => 'Screenshot: availability calendar with time slots',
                'card_title' => 'Holiday mode',
                'card_sub' => 'On until Aug 12',
            ],
            'bookings' => [
                'label' => 'Bookings & payouts',
                'title' => 'Accept and get paid',
                'text' => 'New requests land in your inbox. Accept or decline with one click, the platform handles invoicing and payouts, itemised to the cent.',
                'bullets' => [
                    'Booking inbox with accept or decline',
                    'Automatic invoices and payout statements',
                    'Secure payouts through Stripe Connect',
                ],
            ],
        ],
    ],

    'receipt' => [
        'title' => 'Payout statement',
        'booking' => 'Booking (3 hrs × € 50)',
        'commission' => 'Commission (9%)',
        'vat' => 'VAT on commission (21%)',
        'net' => 'Your payout',
        'footer' => 'Processed automatically via Stripe Connect',
    ],

    'included' => [
        'title' => 'Everything in your dashboard',
        'subtitle' => 'The host dashboard is your command center: everything you need to run your studio, in one place.',
        'tiles' => [
            'inbox' => [
                'title' => 'Booking inbox',
                'text' => 'Accept or decline requests with one click and see exactly what is scheduled.',
                'request_title' => 'New request',
                'request_sub' => '3 hrs · Sat 14:00 – 17:00',
                'accept' => 'Accept',
                'decline' => 'Decline',
            ],
            'agenda' => ['title' => 'Calendar & blocks', 'text' => 'Weekly schedule, block your own sessions and holiday mode.'],
            'revenue' => ['title' => 'Revenue overview', 'text' => 'Gross, commission and net at a glance.'],
            'invoices' => ['title' => 'Invoices & statements', 'text' => 'Generated automatically and always available to download.'],
            'rooms' => ['title' => 'Rooms & pricing', 'text' => 'Multiple rooms, each with its own rate and minimum booking duration.'],
            'stripe' => ['title' => 'Stripe onboarding', 'text' => 'Secure identity and bank verification through your personal checklist, your listing goes live once everything is green.'],
        ],
    ],

    'steps' => [
        'title' => 'Live in four steps',
        'subtitle' => 'From sign-up to your first payout.',
        'items' => [
            ['title' => 'Sign up', 'text' => 'Create an account and enter your studio details.'],
            ['title' => 'Add your room', 'text' => 'Upload photos, set your hourly rate and availability and describe your equipment.'],
            ['title' => 'Go live', 'text' => 'After a quick review your studio goes online and becomes discoverable.'],
            ['title' => 'Get bookings', 'text' => 'Accept requests and get paid out automatically after each session.'],
        ],
    ],

    'commission' => [
        'label' => 'Commission',
        'value' => '9%',
        'per_booking' => 'per booking',
        'text' => "It's that simple. You only pay when you earn, no fixed costs upfront.",
        'bullets' => [
            'No subscription',
            'No setup fees',
            'No hidden costs on top of the commission',
        ],
    ],

    'faq' => [
        'title' => 'Frequently asked questions',
        'items' => [
            ['q' => 'What does it cost to join?', 'a' => 'Signing up is free. You only pay 9% commission per successful booking, no subscription or setup fees.'],
            ['q' => 'When do I get paid?', 'a' => 'Payouts run through Stripe Connect and are released shortly after the session, once the dispute window has passed.'],
            ['q' => 'Do I have to rent exclusively through StudioMatch?', 'a' => "No. You're free to keep renting your space through your own channels too."],
            ['q' => 'Will I have to manage my calendar twice?', 'a' => 'No. You manage your availability entirely in StudioMatch; booked times automatically disappear from search results.'],
            ['q' => 'What about VAT and invoices?', 'a' => 'The platform automatically generates the correct invoices based on your VAT status. You never have to do it manually.'],
        ],
    ],

    'cta' => [
        'title' => 'Ready to rent out your studio?',
        'text' => 'Sign up today and start receiving bookings through StudioMatch.',
        'button' => 'List your studio',
        'mock' => 'Screenshot: mobile host dashboard',
    ],
];
