<?php

return [
    'meta_title' => 'How StudioMatch works',
    'meta_description' => 'How to book a studio through StudioMatch: search and compare studios, pay securely via iDEAL or credit card and get into the recording room in no time. Transparent pricing and clear cancellation terms.',

    'hero' => [
        'heading' => 'How StudioMatch works',
        'subtitle' => 'From search to session in three steps. Transparent pricing, secure payment and clear agreements, so you always know where you stand.',
        'for_studios' => 'Renting out a studio? Check the page for studios',
    ],

    'steps' => [
        'items' => [
            'search' => [
                'step' => '01',
                'label' => 'Search',
                'title' => 'Search & compare',
                'text' => "Filter by location, price, date, equipment and more. You only see times that are actually free, what you see is what you can book.",
                'bullets' => [
                    'Map and list side by side',
                    'Filter by equipment, DAWs and amenities',
                    'Only genuinely available time slots',
                ],
                'mock' => 'Screenshot: search results with map and filters',
            ],
            'book' => [
                'step' => '02',
                'label' => 'Book',
                'title' => 'Book & pay securely',
                'text' => 'Pick your time slot and pay via iDEAL or credit card. During checkout your slot is reserved for you for 15 minutes, nobody can cut in.',
                'bullets' => [
                    'Full price breakdown before you pay',
                    'Pay via iDEAL or credit card',
                    'Slot reserved for 15 minutes during checkout',
                ],
                'mock' => 'Screenshot: checkout with price breakdown',
                'card_title' => 'Slot reserved',
                'card_sub' => '14:59 minutes left',
            ],
            'session' => [
                'step' => '03',
                'label' => 'Session',
                'title' => 'Confirmation & session',
                'text' => "The studio confirms your request. You then receive the address and contact details, plus a reminder 24 hours in advance. Studio doesn't respond or declines? You automatically get everything back.",
                'bullets' => [
                    'Confirmation with address and contact details',
                    'Reminder 24 hours before your session',
                    'No confirmation = automatic 100% refund',
                ],
                'mock' => 'Screenshot: booking confirmation in the dashboard',
                'card_title' => 'Booking confirmed',
                'card_sub' => 'Sat 14:00 – 17:00',
            ],
        ],
    ],

    'pricing' => [
        'label' => 'Transparent',
        'title' => 'You know exactly what you pay upfront',
        'text' => 'The price consists of the studio rent plus a 9% service fee (and VAT on that fee). No surprises at checkout, the full breakdown is already in your cart.',
        'bullets' => [
            'Hourly rate × hours, straight to the studio',
            '9% service fee for the platform',
            'Invoice or proof of payment automatically in your dashboard',
        ],
        'receipt' => [
            'title' => 'Price breakdown',
            'rent' => 'Rent (3 hrs × € 50)',
            'fee' => 'Service fee (9%)',
            'vat' => 'VAT on service fee (21%)',
            'total' => 'Total',
            'footer' => 'Paid securely via Stripe',
        ],
    ],

    'cancel' => [
        'title' => 'Cancelling or rescheduling',
        'subtitle' => 'Plans change, these are the rules.',
        'tiers' => [
            ['when' => 'More than 48 hours ahead', 'refund' => '100% refund'],
            ['when' => '24 to 48 hours ahead', 'refund' => '50% refund'],
            ['when' => 'Within 24 hours', 'refund' => 'No refund'],
        ],
        'reschedule_title' => 'Rather reschedule?',
        'reschedule' => 'Up to 48 hours before the start you can reschedule once, free of charge, to a free slot, same room, same duration.',
        'problem_title' => 'Problem at the studio?',
        'problem' => 'Something not right? Report it up to 24 hours after the start time via the "Report a problem" button. The payout to the studio is paused immediately and StudioMatch mediates.',
    ],

    'trust' => [
        'title' => 'Book with peace of mind',
        'items' => [
            ['icon' => 'fa-lock', 'title' => 'Secure payment', 'text' => 'All payments run through Stripe, your payment details never reach the studio.'],
            ['icon' => 'fa-hand-holding-dollar', 'title' => 'Money released only after your session', 'text' => 'The payout to the studio follows after your session. If something goes wrong, your payment is safely on hold.'],
            ['icon' => 'fa-file-contract', 'title' => 'Clear house rules', 'text' => 'Capacity, house rules and terms are on the studio page upfront, you know exactly where you stand.'],
        ],
    ],

    'faq' => [
        'title' => 'Frequently asked questions',
        'items' => [
            ['q' => 'Do I need an account to book?', 'a' => 'Yes, booking requires a free account. You can also register at the moment you book a studio, right inside the booking flow.'],
            ['q' => 'How can I pay?', 'a' => 'With iDEAL or credit card. Payment is processed securely via Stripe.'],
            ['q' => 'What if the studio declines my request?', 'a' => "You automatically get a full refund, including when the studio doesn't respond in time."],
            ['q' => 'Can I change my booking?', 'a' => 'You can reschedule once, up to 48 hours in advance, to a free slot in the same room with the same duration. Want to change something else? Cancel and book again.'],
            ['q' => 'What if something is wrong at the studio?', 'a' => 'Report it within 24 hours after the start time via "Report a problem" in your dashboard. The payout is paused and StudioMatch mediates between you and the studio.'],
            ['q' => 'In which cities can I find studios?', 'a' => 'All across the Netherlands, from Amsterdam to Maastricht. Use the search to find studios near you.'],
        ],
    ],

    'cta' => [
        'title' => 'Ready for your next session?',
        'text' => 'Find a studio that fits you and book directly online.',
        'button' => 'Find a studio',
    ],
];
