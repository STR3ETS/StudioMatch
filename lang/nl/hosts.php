<?php

return [
    'meta_title' => "Voor studio's: verhuur jouw studio",
    'meta_description' => "Zet je opname- of mix/master-studio op StudioMatch, verhoog je bezettingsgraad en laat het platform de administratie en betaling regelen. Gratis aanmelden, zonder kosten voor verhuurders.",

    'hero' => [
        'eyebrow' => 'Voor studio-eigenaren',
        'heading' => 'Vul je studio met boekingen',
        'subtitle' => 'Kom uit de anonimiteit van mond-tot-mond en Instagram. Zet je studio op de kaart, beheer je beschikbaarheid op één plek en laat StudioMatch de administratie en betaling regelen.',
        'cta' => 'Studio aanmelden',
        'secondary' => 'Zo werkt het',
        'mock' => 'Screenshot: verhuurder-dashboard met omzetoverzicht',
        'cards' => [
            'booking_title' => 'Nieuwe aanvraag',
            'booking_sub' => 'za 14:00 – 17:00 · Opname',
            'payout_title' => 'Uitbetaling verzonden',
            'payout_sub' => '€ 150,00 via Stripe',
        ],
        'stats' => [
            ['value' => '€ 0', 'label' => 'kosten voor jou als verhuurder'],
            ['value' => '24/7', 'label' => 'online boekbaar voor artiesten'],
            ['value' => '100%', 'label' => 'jouw agenda, jouw regels'],
        ],
    ],

    'statement' => [
        'no' => 'Geen',
        'lines' => ['kosten.', 'exclusiviteit.', 'dubbel agendabeheer.'],
        'sub' => 'De drie grootste zorgen van studio-eigenaren hebben we bewust uit het model gesloopt. Wat overblijft: boekingen, betaald.',
    ],

    'benefits' => [
        'title' => 'Waarom StudioMatch',
        'subtitle' => 'Meer bezetting, minder gedoe. Wij verbinden je met artiesten die actief naar studiotijd zoeken.',
        'items' => [
            'exposure' => ['title' => 'Meer boekingen', 'text' => 'Bereik een brede groep artiesten die actief zoeken naar studiotijd en verhoog je bezettingsgraad.'],
            'admin' => ['title' => 'Minder administratie', 'text' => 'Van beschikbaarheid tot facturatie: het platform neemt het werk uit handen, zodat jij je op opnemen richt.'],
            'payout' => ['title' => 'Snel uitbetaald', 'text' => 'Betalingen lopen veilig via Stripe. Je ontvangt je geld snel, zonder lange wachttijden.'],
            'control' => ['title' => 'Jij houdt de regie', 'text' => 'Jij bepaalt je tarief, je beschikbaarheid en je huisregels, en accepteert elke boeking zelf.'],
        ],
    ],

    'features' => [
        'title' => 'Alles voor de verhuurder',
        'subtitle' => 'Van je eerste listing tot de uitbetaling: StudioMatch geeft je de tools om je studio moeiteloos te verhuren.',
        'items' => [
            'dashboard' => [
                'label' => 'Dashboard',
                'title' => 'Jouw commandocentrum',
                'text' => 'Beheer je studio, ruimtes en gegevens vanuit één overzichtelijk dashboard, op desktop én mobiel.',
                'bullets' => [
                    'Ruimtes toevoegen, bewerken of verwijderen',
                    'Prijzen en minimale boekingsduur aanpassen wanneer je wilt',
                    'Omzet en uitbetalingen in één oogopslag',
                ],
                'mock' => 'Screenshot: verhuurder-dashboard met omzetoverzicht',
                'card_title' => 'Netto omzet',
                'card_sub' => '€ 1.240 deze maand',
            ],
            'availability' => [
                'label' => 'Beschikbaarheid',
                'title' => 'Jij bepaalt wanneer je open bent',
                'text' => 'Stel een wekelijks schema in, blokkeer eigen sessies en zet je studio in vakantiemodus. Alles in één agenda, geen dubbel werk.',
                'bullets' => [
                    'Wekelijks schema per ruimte',
                    'Blokkades voor eigen sessies of onderhoud',
                    'Vakantiemodus: tijdelijk onzichtbaar',
                ],
                'mock' => 'Screenshot: beschikbaarheidskalender met tijdsloten',
                'card_title' => 'Vakantiemodus',
                'card_sub' => 'Aan t/m 12 aug',
            ],
            'bookings' => [
                'label' => 'Boekingen & uitbetaling',
                'title' => 'Accepteer en word uitbetaald',
                'text' => 'Nieuwe aanvragen komen binnen in je inbox. Accepteren of weigeren doe je met één klik, de facturatie en uitbetaling regelt het platform, tot op de cent gespecificeerd.',
                'bullets' => [
                    'Boekingsinbox met accepteren of weigeren',
                    'Automatische facturen en uitbetalingsspecificaties',
                    'Veilige uitbetaling via Stripe Connect',
                ],
            ],
        ],
    ],

    'receipt' => [
        'title' => 'Uitbetalingsspecificatie',
        'booking' => 'Boeking (3 uur × € 50)',
        'fee' => 'Kosten voor jou als verhuurder',
        'net' => 'Jouw uitbetaling',
        'footer' => 'Automatisch verwerkt via Stripe Connect',
    ],

    'included' => [
        'title' => 'Alles in je dashboard',
        'subtitle' => 'Het verhuurdersdashboard is je commandocentrum: alles wat je nodig hebt om je studio te runnen, op één plek.',
        'tiles' => [
            'inbox' => [
                'title' => 'Boekingsinbox',
                'text' => 'Accepteer of weiger aanvragen met één klik en zie direct wat er gepland staat.',
                'request_title' => 'Nieuwe aanvraag',
                'request_sub' => '3 uur · za 14:00 – 17:00',
                'accept' => 'Accepteren',
                'decline' => 'Weigeren',
            ],
            'agenda' => ['title' => 'Agenda & blokkades', 'text' => 'Wekelijks schema, eigen sessies blokkeren en vakantiemodus.'],
            'revenue' => ['title' => 'Omzetoverzicht', 'text' => 'Je omzet en uitbetalingen in één oogopslag.'],
            'invoices' => ['title' => 'Facturen & specificaties', 'text' => 'Automatisch gegenereerd en altijd te downloaden.'],
            'rooms' => ['title' => 'Ruimtes & prijzen', 'text' => 'Meerdere ruimtes, elk met een eigen tarief en minimale boekingsduur.'],
            'stripe' => ['title' => 'Stripe-onboarding', 'text' => 'Veilige identiteits- en bankverificatie via je persoonlijke checklist, je listing gaat live zodra alles groen is.'],
        ],
    ],

    'steps' => [
        'title' => 'Live in vier stappen',
        'subtitle' => 'Van aanmelding tot je eerste uitbetaling.',
        'items' => [
            ['title' => 'Meld je aan', 'text' => 'Maak een account aan en vul je studiogegevens in.'],
            ['title' => 'Voeg je ruimte toe', 'text' => "Upload foto's, stel je uurtarief en beschikbaarheid in en beschrijf je apparatuur."],
            ['title' => 'Ga live', 'text' => 'Na een korte controle staat je studio online en is hij vindbaar.'],
            ['title' => 'Ontvang boekingen', 'text' => 'Accepteer aanvragen en word na elke sessie automatisch uitbetaald.'],
        ],
    ],

    'payout' => [
        'label' => 'Jouw uitbetaling',
        'value' => '100%',
        'suffix' => 'van jouw uurtarief',
        'text' => 'Jij bepaalt je tarief en dat is precies wat je ontvangt. StudioMatch regelt de boekingen, betalingen en administratie eromheen, zodat jij je op de sessies kunt richten.',
        'bullets' => [
            'Automatische facturen en uitbetalingen',
            'Eén realtime agenda, geen dubbel beheer',
            'Meer bezetting via artiesten die actief zoeken',
        ],
    ],

    'faq' => [
        'title' => 'Veelgestelde vragen',
        'items' => [
            ['q' => 'Wat kost het om mee te doen?', 'a' => 'Niets. Aanmelden is gratis en er zijn geen abonnements- of opstartkosten. De servicekosten van het platform worden bij de boeking aan de artiest berekend; jij ontvangt gewoon je volledige uurtarief.'],
            ['q' => 'Wanneer word ik uitbetaald?', 'a' => 'Uitbetalingen lopen via Stripe Connect en worden kort na de sessie vrijgegeven, zodra het dispuutvenster is verstreken.'],
            ['q' => 'Moet ik exclusief via StudioMatch verhuren?', 'a' => 'Nee. Je blijft vrij om je ruimte ook via je eigen kanalen te verhuren.'],
            ['q' => 'Beheer ik mijn agenda dan dubbel?', 'a' => 'Nee. Je beheert je beschikbaarheid volledig in StudioMatch; geboekte tijden verdwijnen automatisch uit de zoekresultaten.'],
            ['q' => 'Hoe zit het met btw en facturen?', 'a' => 'Het platform genereert automatisch de juiste facturen op basis van je btw-status. Jij hoeft niets handmatig te doen.'],
        ],
    ],

    'cta' => [
        'title' => 'Klaar om je studio te verhuren?',
        'text' => 'Meld je vandaag aan en ontvang je eerste boekingen via StudioMatch.',
        'button' => 'Studio aanmelden',
        'mock' => 'Screenshot: mobiel verhuurder-dashboard',
    ],
];
