<?php

// Teksten van de iOS/Android-app (mobile/app). De app haalt 'ui' op via api/app/teksten (ook vóór het
// inloggen) en via api/app/ik; de rest gebruikt de API zelf in wat hij terugstuurt.
return [
    'roles' => [
        'artiest' => 'Artiest',
        'verhuurder' => 'Verhuurder',
        'admin' => 'Beheerder',
    ],

    'errors' => [
        'wrong_role' => 'Dit scherm hoort bij een ander soort account.',
    ],

    'menu' => [
        'bookings' => 'Boekingen',
        'search' => 'Zoeken',
        'account' => 'Account',
        'inbox' => 'Aanvragen',
        'agenda' => 'Agenda',
        'account_details' => 'Accountgegevens',
        'how' => 'Hoe het werkt',
        'contact' => 'Contact',
    ],

    'price' => [
        'rent' => 'Huur',
        'engineer' => 'Technicus',
        'service_fee' => 'Servicekosten',
        'vat' => 'Btw',
    ],

    'ui' => [
        'login' => [
            'title' => 'Welkom terug',
            'subtitle' => 'Log in om je boekingen te bekijken en te beheren.',
            'email' => 'E-mailadres',
            'password' => 'Wachtwoord',
            'submit' => 'Inloggen',
            'busy' => 'Bezig met inloggen',
            'forgot' => 'Wachtwoord vergeten?',
            'no_account' => 'Nog geen account?',
            'register' => 'Registreren',
            'no_connection' => 'Geen verbinding met de server. Controleer je internet en probeer het opnieuw.',
        ],
        'common' => [
            'retry' => 'Opnieuw proberen',
            'back' => 'Terug',
            'offline_since' => 'Geen verbinding. Je ziet de gegevens van :time.',
            'offline' => 'Geen verbinding. Zodra je weer online bent, wordt alles bijgewerkt.',
            'failed' => 'Dat lukte niet. Controleer de verbinding en probeer het opnieuw.',
            'no_data' => 'Geen verbinding met de server.',
            'yes' => 'Ja',
            'cancel' => 'Annuleren',
            'opening' => 'Pagina openen',
            'today' => 'vandaag',
        ],
        'bookings' => [
            'title' => 'Mijn boekingen',
            'upcoming' => 'Komend',
            'past' => 'Eerder',
            'empty_upcoming' => 'Je hebt nog geen komende boekingen.',
            'empty_past' => 'Nog geen eerdere boekingen.',
            'find_studio' => 'Zoek een studio',
        ],
        'booking' => [
            'prices' => 'Prijsopbouw',
            'total' => 'Totaal',
            'refunded' => 'Terugbetaald',
            'address' => 'Adres',
            'phone' => 'Telefoon',
            'address_later' => 'Het adres zie je zodra de studio je boeking heeft bevestigd.',
            'view_studio' => 'Bekijk de studio',
            'actions' => 'Wat wil je doen?',
            'not_found' => 'Deze boeking bestaat niet (meer).',
        ],
        'studios' => [
            'title' => 'Studio zoeken',
            'search' => 'Plaats, studio of ruimte',
            'all' => 'Alles',
            'per_hour' => 'per uur',
            'per_day' => 'per dag',
            'empty' => 'Geen studio\'s gevonden. Probeer een andere plaats of zoekterm.',
            'count_one' => '1 studio',
            'count_many' => ':count studio\'s',
        ],
        'inbox' => [
            'title' => 'Aanvragen',
            'requests' => 'Nieuwe aanvragen',
            'upcoming' => 'Komende sessies',
            'empty_requests' => 'Geen openstaande aanvragen.',
            'empty_upcoming' => 'Geen komende sessies.',
            'accept' => 'Accepteren',
            'decline' => 'Weigeren',
            'decline_confirm' => 'Deze aanvraag weigeren? De artiest krijgt het volledige bedrag terug.',
            'respond_before' => 'Reageer vóór :time',
            'with_engineer' => 'Met technicus',
            'you_receive' => 'Jij ontvangt',
        ],
        'agenda' => [
            'title' => 'Agenda',
            'nothing' => 'Niets gepland',
            'pending' => 'Wacht op jouw reactie',
        ],
        'account' => [
            'title' => 'Account',
            'language' => 'Taal',
            'more' => 'Meer',
            'logout' => 'Uitloggen',
            'logout_confirm' => 'Uitloggen op deze telefoon?',
            'version' => 'StudioMatch-app',
        ],
    ],
];
