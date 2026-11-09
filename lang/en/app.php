<?php

// Texts of the iOS/Android app (mobile/app). The app fetches 'ui' via api/app/teksten (also before
// logging in) and via api/app/ik; the API itself uses the rest in what it returns.
return [
    'roles' => [
        'artiest' => 'Artist',
        'verhuurder' => 'Studio owner',
        'admin' => 'Administrator',
    ],

    'errors' => [
        'wrong_role' => 'This screen belongs to a different kind of account.',
    ],

    'menu' => [
        'bookings' => 'Bookings',
        'search' => 'Search',
        'account' => 'Account',
        'inbox' => 'Requests',
        'agenda' => 'Agenda',
        'account_details' => 'Account details',
        'how' => 'How it works',
        'contact' => 'Contact',
    ],

    'price' => [
        'rent' => 'Rent',
        'engineer' => 'Engineer',
        'service_fee' => 'Service fee',
        'vat' => 'VAT',
    ],

    'ui' => [
        'login' => [
            'title' => 'Welcome back',
            'subtitle' => 'Log in to view and manage your bookings.',
            'email' => 'Email address',
            'password' => 'Password',
            'submit' => 'Log in',
            'busy' => 'Logging in',
            'forgot' => 'Forgot your password?',
            'no_account' => 'No account yet?',
            'register' => 'Sign up',
            'no_connection' => 'No connection to the server. Check your internet and try again.',
        ],
        'common' => [
            'retry' => 'Try again',
            'back' => 'Back',
            'offline_since' => 'No connection. You are seeing the data from :time.',
            'offline' => 'No connection. Everything will update as soon as you are back online.',
            'failed' => 'That did not work. Check your connection and try again.',
            'no_data' => 'No connection to the server.',
            'yes' => 'Yes',
            'cancel' => 'Cancel',
            'opening' => 'Opening page',
            'today' => 'today',
        ],
        'bookings' => [
            'title' => 'My bookings',
            'upcoming' => 'Upcoming',
            'past' => 'Past',
            'empty_upcoming' => 'You have no upcoming bookings yet.',
            'empty_past' => 'No past bookings yet.',
            'find_studio' => 'Find a studio',
        ],
        'booking' => [
            'prices' => 'Price breakdown',
            'total' => 'Total',
            'refunded' => 'Refunded',
            'address' => 'Address',
            'phone' => 'Phone',
            'address_later' => 'You will see the address once the studio has confirmed your booking.',
            'view_studio' => 'View the studio',
            'actions' => 'What would you like to do?',
            'not_found' => 'This booking does not exist (anymore).',
        ],
        'studios' => [
            'title' => 'Find a studio',
            'search' => 'City, studio or room',
            'all' => 'All',
            'per_hour' => 'per hour',
            'per_day' => 'per day',
            'empty' => 'No studios found. Try another city or search term.',
            'count_one' => '1 studio',
            'count_many' => ':count studios',
        ],
        'inbox' => [
            'title' => 'Requests',
            'requests' => 'New requests',
            'upcoming' => 'Upcoming sessions',
            'empty_requests' => 'No open requests.',
            'empty_upcoming' => 'No upcoming sessions.',
            'accept' => 'Accept',
            'decline' => 'Decline',
            'decline_confirm' => 'Decline this request? The artist gets the full amount back.',
            'respond_before' => 'Respond before :time',
            'with_engineer' => 'With engineer',
            'you_receive' => 'You receive',
        ],
        'agenda' => [
            'title' => 'Agenda',
            'nothing' => 'Nothing planned',
            'pending' => 'Waiting for your response',
        ],
        'account' => [
            'title' => 'Account',
            'language' => 'Language',
            'more' => 'More',
            'logout' => 'Log out',
            'logout_confirm' => 'Log out on this phone?',
            'version' => 'StudioMatch app',
        ],
    ],
];
