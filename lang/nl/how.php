<?php

return [
    'meta_title' => 'Hoe werkt StudioMatch',
    'meta_description' => "Zo boek je een studio via StudioMatch: zoek en vergelijk studio's, betaal veilig via iDEAL of creditcard en sta binnen no-time in de opnameruimte. Transparante prijzen en duidelijke annuleringsvoorwaarden.",

    'hero' => [
        'heading' => 'Hoe werkt StudioMatch',
        'subtitle' => 'Van zoeken tot sessie in drie stappen. Transparante prijzen, veilige betaling en duidelijke afspraken. Zo weet je altijd waar je aan toe bent.',
        'for_studios' => "Studio verhuren? Bekijk de pagina voor studio's",
    ],

    'steps' => [
        'items' => [
            'search' => [
                'step' => '01',
                'label' => 'Zoeken',
                'title' => 'Zoek & vergelijk',
                'text' => "Filter op plaats, prijs, datum, apparatuur en meer. Je ziet alleen tijden die écht vrij zijn, wat je ziet, kun je boeken.",
                'bullets' => [
                    'Kaart en lijst naast elkaar',
                    "Filter op apparatuur, DAW's en voorzieningen",
                    'Alleen daadwerkelijk beschikbare tijdsloten',
                ],
                'mock' => 'Screenshot: zoekresultaten met kaart en filters',
            ],
            'book' => [
                'step' => '02',
                'label' => 'Boeken',
                'title' => 'Boek & betaal veilig',
                'text' => 'Kies je tijdslot en reken af via iDEAL of creditcard. Tijdens het afrekenen staat je slot 15 minuten voor jou gereserveerd, niemand kan er tussendoor.',
                'bullets' => [
                    'Volledige prijsopbouw vóór je betaalt',
                    'Betalen via iDEAL of creditcard',
                    'Slot 15 minuten gereserveerd tijdens het afrekenen',
                ],
                'mock' => 'Screenshot: checkout met prijsopbouw',
                'card_title' => 'Slot gereserveerd',
                'card_sub' => 'Nog 14:59 minuten',
            ],
            'session' => [
                'step' => '03',
                'label' => 'Sessie',
                'title' => 'Bevestiging & sessie',
                'text' => 'De studio bevestigt je aanvraag. Daarna ontvang je het adres en de contactgegevens, plus een herinnering 24 uur vooraf. Reageert de studio niet of weigert hij? Dan krijg je automatisch alles terug.',
                'bullets' => [
                    'Bevestiging met adres en contactgegevens',
                    'Herinnering 24 uur voor je sessie',
                    'Geen bevestiging = automatisch 100% terugbetaald',
                ],
                'mock' => 'Screenshot: boekingsbevestiging in het dashboard',
                'card_title' => 'Boeking bevestigd',
                'card_sub' => 'za 14:00 – 17:00',
            ],
        ],
    ],

    'pricing' => [
        'label' => 'Transparant',
        'title' => 'Je weet vooraf precies wat je betaalt',
        'text' => 'De prijs is opgebouwd uit de huur van de studio plus 9% servicekosten (en btw daarover). Geen verrassingen bij het afrekenen, de volledige opbouw staat al in je winkelmandje.',
        'bullets' => [
            'Uurtarief × aantal uren, rechtstreeks naar de studio',
            '9% servicekosten voor het platform',
            'Factuur of betaalbewijs automatisch in je dashboard',
        ],
        'receipt' => [
            'title' => 'Prijsopbouw',
            'rent' => 'Huur (3 uur × € 50)',
            'fee' => 'Servicekosten (9%)',
            'vat' => 'Btw over servicekosten (21%)',
            'total' => 'Totaal',
            'footer' => 'Veilig betaald via Stripe',
        ],
    ],

    'cancel' => [
        'title' => 'Annuleren of verzetten',
        'subtitle' => 'Plannen veranderen, dit zijn de afspraken.',
        'tiers' => [
            ['when' => 'Meer dan 48 uur vooraf', 'refund' => '100% terug'],
            ['when' => '24 tot 48 uur vooraf', 'refund' => '50% terug'],
            ['when' => 'Binnen 24 uur', 'refund' => 'Geen restitutie'],
        ],
        'reschedule_title' => 'Liever verzetten?',
        'reschedule' => 'Tot 48 uur vóór aanvang verzet je je boeking eenmalig kosteloos naar een vrij tijdslot, zelfde ruimte, zelfde duur.',
        'problem_title' => 'Probleem in de studio?',
        'problem' => 'Klopt er iets niet? Meld het tot 24 uur na de starttijd via de knop "Meld een probleem". De uitbetaling aan de studio wordt dan direct gepauzeerd en StudioMatch bemiddelt.',
    ],

    'trust' => [
        'title' => 'Boeken met een gerust hart',
        'items' => [
            ['icon' => 'fa-lock', 'title' => 'Veilig betalen', 'text' => 'Alle betalingen lopen via Stripe, je betaalgegevens komen nooit bij de studio terecht.'],
            ['icon' => 'fa-hand-holding-dollar', 'title' => 'Geld pas vrijgegeven na je sessie', 'text' => 'De uitbetaling aan de studio volgt pas na afloop. Gaat er iets mis, dan staat je betaling veilig on hold.'],
            ['icon' => 'fa-file-contract', 'title' => 'Duidelijke huisregels', 'text' => 'Capaciteit, huisregels en voorwaarden staan vooraf op de studiopagina, je weet precies waar je aan toe bent.'],
        ],
    ],

    'faq' => [
        'title' => 'Veelgestelde vragen',
        'items' => [
            ['q' => 'Heb ik een account nodig om te boeken?', 'a' => 'Ja, boeken doe je met een gratis account. Je kunt je ook pas registreren op het moment dat je een studio boekt, dat kan gewoon in de boekflow.'],
            ['q' => 'Hoe kan ik betalen?', 'a' => 'Met iDEAL of creditcard. De betaling verloopt beveiligd via Stripe.'],
            ['q' => 'Wat als de studio mijn aanvraag weigert?', 'a' => 'Dan krijg je automatisch het volledige bedrag terug, ook als de studio niet op tijd reageert.'],
            ['q' => 'Kan ik mijn boeking wijzigen?', 'a' => 'Verzetten kan eenmalig tot 48 uur vooraf, naar een vrij tijdslot in dezelfde ruimte met dezelfde duur. Iets anders wijzigen? Dan annuleer je en boek je opnieuw.'],
            ['q' => 'Wat als er iets mis is in de studio?', 'a' => 'Meld het binnen 24 uur na de starttijd via "Meld een probleem" in je dashboard. De uitbetaling wordt gepauzeerd en StudioMatch bemiddelt tussen jou en de studio.'],
            ['q' => 'In welke steden vind ik studio\'s?', 'a' => 'Door heel Nederland, van Amsterdam tot Maastricht. Gebruik de zoekfunctie om studio\'s bij jou in de buurt te vinden.'],
        ],
    ],

    'cta' => [
        'title' => 'Klaar voor je volgende sessie?',
        'text' => 'Vind een studio die bij je past en boek direct online.',
        'button' => 'Zoek een studio',
    ],
];
