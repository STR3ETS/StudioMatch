<?php

return [
    'meta_title' => 'Veelgestelde vragen',
    'meta_description' => 'Antwoorden op de meestgestelde vragen over boeken, betalen, annuleren en het verhuren van je studio via StudioMatch.',

    'hero' => [
        'heading' => 'Veelgestelde vragen',
        'subtitle' => 'Alles over boeken, betalen, annuleren en verhuren. Staat je vraag er niet tussen? Neem gerust contact op.',
    ],

    'categories' => [
        'booking' => [
            'title' => 'Boeken & betalen',
            'items' => [
                ['q' => 'Heb ik een account nodig om te boeken?', 'a' => 'Ja, boeken doe je met een gratis account. Je kunt je ook pas registreren op het moment dat je een studio boekt, dat kan gewoon in de boekflow.'],
                ['q' => 'Hoe kan ik betalen?', 'a' => 'Met iDEAL of creditcard. De betaling verloopt beveiligd via Stripe.'],
                ['q' => 'Wat betaal ik bovenop de huur?', 'a' => 'Naast de huur betaal je 9% servicekosten en btw over die servicekosten. De volledige opbouw zie je vóór je betaalt, geen verrassingen achteraf.'],
                ['q' => 'Hoe lang staat mijn tijdslot vast tijdens het afrekenen?', 'a' => 'Zodra je begint met afrekenen staat het slot 15 minuten voor jou gereserveerd. Rond je de betaling niet af, dan komt het slot automatisch weer vrij.'],
                ['q' => 'Wanneer is mijn boeking definitief?', 'a' => 'Na acceptatie door de studio. Je ontvangt dan een bevestiging met het adres en de contactgegevens. Reageert de studio niet op tijd of weigert hij, dan krijg je automatisch het volledige bedrag terug.'],
            ],
        ],
        'cancel' => [
            'title' => 'Annuleren & verzetten',
            'items' => [
                ['q' => 'Wat zijn de annuleringsvoorwaarden?', 'a' => 'Meer dan 48 uur vooraf annuleren is kosteloos. Tussen 24 en 48 uur vooraf krijg je 50% terug. Binnen 24 uur is er geen restitutie.'],
                ['q' => 'Kan ik mijn boeking verzetten?', 'a' => 'Ja, eenmalig tot 48 uur vooraf, naar een vrij tijdslot in dezelfde ruimte met dezelfde duur. Iets anders wijzigen? Dan annuleer je en boek je opnieuw.'],
                ['q' => 'Wat als de studio mijn aanvraag weigert?', 'a' => 'Dan krijg je automatisch het volledige bedrag terug, ook als de studio niet op tijd reageert.'],
                ['q' => 'Wat als er iets mis is in de studio?', 'a' => 'Meld het binnen 24 uur na de starttijd via "Meld een probleem" in je dashboard. De uitbetaling aan de studio wordt gepauzeerd en StudioMatch bemiddelt.'],
            ],
        ],
        'hosts' => [
            'title' => "Voor studio's",
            'items' => [
                ['q' => 'Wat kost het om mijn studio aan te melden?', 'a' => 'Aanmelden is gratis. Je betaalt alleen 9% commissie per succesvolle boeking, geen abonnement of opstartkosten.'],
                ['q' => 'Wanneer word ik uitbetaald?', 'a' => 'Uitbetalingen lopen via Stripe Connect en worden kort na de sessie vrijgegeven, zodra het dispuutvenster is verstreken.'],
                ['q' => 'Moet ik exclusief via StudioMatch verhuren?', 'a' => 'Nee. Je blijft vrij om je ruimte ook via je eigen kanalen te verhuren.'],
                ['q' => 'Beheer ik mijn agenda dan dubbel?', 'a' => 'Nee. Je beheert je beschikbaarheid volledig in StudioMatch; geboekte tijden verdwijnen automatisch uit de zoekresultaten.'],
                ['q' => 'Hoe zit het met btw en facturen?', 'a' => 'Het platform genereert automatisch de juiste facturen op basis van je btw-status. Jij hoeft niets handmatig te doen.'],
            ],
        ],
        'account' => [
            'title' => 'Account & platform',
            'items' => [
                ['q' => 'Is StudioMatch beschikbaar in het Engels?', 'a' => 'Ja. Je wisselt van taal via de taalkeuze in het menu, rechtsboven op elke pagina.'],
                ['q' => 'Kan ik mijn account verwijderen?', 'a' => 'Ja, dat doe je zelf via je dashboard. Je gegevens worden dan verwijderd conform de AVG.'],
                ['q' => 'In welke steden vind ik studio\'s?', 'a' => 'Door heel Nederland, van Amsterdam tot Maastricht. Gebruik de zoekfunctie om studio\'s bij jou in de buurt te vinden.'],
            ],
        ],
    ],

    'cta' => [
        'title' => 'Antwoord niet gevonden?',
        'text' => 'Stuur ons een bericht, we reageren doorgaans binnen één werkdag.',
        'button' => 'Neem contact op',
    ],
];
