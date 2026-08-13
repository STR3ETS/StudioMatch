<?php

return [
    'number' => 'Documentnummer',
    'date' => 'Datum',
    'reference' => 'Verwijst naar',
    'from' => 'Van',
    'to' => 'Aan',
    'description' => 'Omschrijving',
    'amount' => 'Bedrag',
    'subtotal' => 'Subtotaal excl. btw',
    'vat' => 'Btw (:rate%)',
    'total' => 'Totaal',
    'footer' => 'Dit document is automatisch opgesteld via StudioMatch (studiomatch.nl). Vragen? Mail naar info@studiomatch.nl.',

    'line_session' => 'Studiohuur :room, :date, :time (:hours uur)',
    'line_fee' => 'Servicekosten StudioMatch bij boeking :number',
    'line_credit' => 'Creditering van document :number',

    'types' => [
        'rent_invoice' => 'Factuur studiohuur',
        'rent_receipt' => 'Huurbevestiging / betaalbewijs',
        'fee_invoice' => 'Factuur servicekosten',
        'credit' => 'Creditnota',
    ],

    'notes' => [
        'rent_invoice' => 'Deze factuur is namens de verhuurder opgesteld en uitgereikt door StudioMatch. De huurprijs is inclusief 21% btw.',
        'rent_receipt' => 'De verhuurder is niet btw-plichtig. Dit document is een huurbevestiging en betaalbewijs, geen btw-factuur.',
        'fee_invoice' => 'De servicekosten worden in rekening gebracht door StudioMatch voor het gebruik van het platform.',
        'credit' => 'Deze creditnota volgt uit een (gedeeltelijke) terugbetaling volgens de annuleringsvoorwaarden.',
    ],

    'labels' => [
        'huur' => 'Factuur huur',
        'commissie' => 'Factuur servicekosten',
        'credit-huur' => 'Creditnota huur',
        'credit-commissie' => 'Creditnota servicekosten',
    ],

    'artist' => [
        'title' => 'Facturen',
        'subtitle' => 'Download hier per boeking je factuur of betaalbewijs en eventuele creditnota\'s.',
        'empty' => 'Nog geen facturen. Zodra je een boeking hebt betaald, vind je hier de documenten.',
    ],

    'host' => [
        'title' => 'Facturen',
        'subtitle' => 'De huurfacturen die StudioMatch namens jou aan artiesten uitreikt, plus eventuele creditnota\'s.',
        'note' => 'Ben je btw-plichtig, dan reiken we namens jou een btw-factuur uit. Zo niet, dan ontvangt de artiest een huurbevestiging. Dit stel je in bij je bedrijfsgegevens.',
        'empty' => 'Nog geen facturen. Zodra een artiest een boeking bij jou betaalt, vind je hier de documenten.',
    ],
];
