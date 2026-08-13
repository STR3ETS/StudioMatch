<?php

return [
    'number' => 'Document number',
    'date' => 'Date',
    'reference' => 'References',
    'from' => 'From',
    'to' => 'To',
    'description' => 'Description',
    'amount' => 'Amount',
    'subtotal' => 'Subtotal excl. VAT',
    'vat' => 'VAT (:rate%)',
    'total' => 'Total',
    'footer' => 'This document was generated automatically via StudioMatch (studiomatch.nl). Questions? Email info@studiomatch.nl.',

    'line_session' => 'Studio rental :room, :date, :time (:hours hours)',
    'line_fee' => 'StudioMatch service fee for booking :number',
    'line_credit' => 'Credit for document :number',

    'types' => [
        'rent_invoice' => 'Studio rental invoice',
        'rent_receipt' => 'Rental confirmation / proof of payment',
        'fee_invoice' => 'Service fee invoice',
        'credit' => 'Credit note',
    ],

    'notes' => [
        'rent_invoice' => 'This invoice was drawn up and issued on behalf of the host by StudioMatch. The rental price includes 21% VAT.',
        'rent_receipt' => 'The host is not VAT-registered. This document is a rental confirmation and proof of payment, not a VAT invoice.',
        'fee_invoice' => 'The service fee is charged by StudioMatch for the use of the platform.',
        'credit' => 'This credit note follows from a (partial) refund according to the cancellation policy.',
    ],

    'labels' => [
        'huur' => 'Rental invoice',
        'commissie' => 'Service fee invoice',
        'credit-huur' => 'Rental credit note',
        'credit-commissie' => 'Service fee credit note',
    ],

    'artist' => [
        'title' => 'Invoices',
        'subtitle' => 'Download your invoice or proof of payment and any credit notes per booking here.',
        'empty' => 'No invoices yet. Once you have paid for a booking, you will find the documents here.',
    ],

    'host' => [
        'title' => 'Invoices',
        'subtitle' => 'The rental invoices StudioMatch issues to artists on your behalf, plus any credit notes.',
        'note' => 'If you are VAT-registered, we issue a VAT invoice on your behalf. If not, the artist receives a rental confirmation. You can set this in your business details.',
        'empty' => 'No invoices yet. Once an artist pays for a booking with you, you will find the documents here.',
    ],
];
