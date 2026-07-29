<?php

return [
    'meta_title' => 'Frequently asked questions',
    'meta_description' => 'Answers to the most frequently asked questions about booking, paying, cancelling and renting out your studio through StudioMatch.',

    'hero' => [
        'heading' => 'Frequently asked questions',
        'subtitle' => "Everything about booking, paying, cancelling and hosting. Can't find your question? Feel free to get in touch.",
    ],

    'categories' => [
        'booking' => [
            'title' => 'Booking & paying',
            'items' => [
                ['q' => 'Do I need an account to book?', 'a' => 'Yes, booking requires a free account. You can also register at the moment you book a studio, right inside the booking flow.'],
                ['q' => 'How can I pay?', 'a' => 'With iDEAL or credit card. Payment is processed securely via Stripe.'],
                ['q' => 'What do I pay on top of the rent?', 'a' => 'Besides the rent you pay a small service contribution. You see the full breakdown before you pay, no surprises afterwards.'],
                ['q' => 'How long is my time slot held during checkout?', 'a' => "Once you start checking out, the slot is reserved for you for 15 minutes. If you don't complete the payment, the slot is automatically released."],
                ['q' => 'When is my booking final?', 'a' => "After the studio accepts it. You then receive a confirmation with the address and contact details. If the studio doesn't respond in time or declines, you automatically get a full refund."],
            ],
        ],
        'cancel' => [
            'title' => 'Cancelling & rescheduling',
            'items' => [
                ['q' => 'What are the cancellation terms?', 'a' => 'Cancelling more than 48 hours ahead is free. Between 24 and 48 hours ahead you get 50% back. Within 24 hours there is no refund.'],
                ['q' => 'Can I reschedule my booking?', 'a' => 'Yes, once, up to 48 hours in advance, to a free slot in the same room with the same duration. Want to change something else? Cancel and book again.'],
                ['q' => 'What if the studio declines my request?', 'a' => "You automatically get a full refund, including when the studio doesn't respond in time."],
                ['q' => 'What if something is wrong at the studio?', 'a' => 'Report it within 24 hours after the start time via "Report a problem" in your dashboard. The payout to the studio is paused and StudioMatch mediates.'],
            ],
        ],
        'hosts' => [
            'title' => 'For studios',
            'items' => [
                ['q' => 'What does it cost to list my studio?', 'a' => 'Signing up is free. You only pay 9% commission per successful booking, no subscription or setup fees.'],
                ['q' => 'When do I get paid?', 'a' => 'Payouts run through Stripe Connect and are released shortly after the session, once the dispute window has passed.'],
                ['q' => 'Do I have to rent exclusively through StudioMatch?', 'a' => "No. You're free to keep renting your space through your own channels too."],
                ['q' => 'Will I have to manage my calendar twice?', 'a' => 'No. You manage your availability entirely in StudioMatch; booked times automatically disappear from search results.'],
                ['q' => 'What about VAT and invoices?', 'a' => 'The platform automatically generates the correct invoices based on your VAT status. You never have to do it manually.'],
            ],
        ],
        'account' => [
            'title' => 'Account & platform',
            'items' => [
                ['q' => 'Is StudioMatch available in English?', 'a' => 'Yes. You switch languages via the language selector in the menu, at the top right of every page.'],
                ['q' => 'Can I delete my account?', 'a' => 'Yes, you can do that yourself from your dashboard. Your data is then removed in accordance with the GDPR.'],
                ['q' => 'In which cities can I find studios?', 'a' => 'All across the Netherlands, from Amsterdam to Maastricht. Use the search to find studios near you.'],
            ],
        ],
    ],

    'cta' => [
        'title' => "Didn't find your answer?",
        'text' => 'Send us a message, we usually respond within one business day.',
        'button' => 'Get in touch',
    ],
];
