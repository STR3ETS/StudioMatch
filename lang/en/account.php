<?php

return [
    'title' => 'Account',
    'subtitle' => 'Manage your details, password and account.',

    'profile' => [
        'title' => 'Your details',
        'name' => 'Name',
        'email' => 'Email address',
        'street' => 'Street + number',
        'postal_code' => 'Postal code',
        'city' => 'City',
        'address_hint' => 'Your address is needed for your invoices and is checked against the Dutch address register.',
        'address_banner' => 'Fill in your address so it appears on your invoices.',
        'address_banner_action' => 'Add address',
        'address_required_title' => 'Add your address first',
        'address_required_text' => 'Your account is not complete yet. Until your address is filled in you cannot book and your dashboard stays locked. Enter your street, postal code and city below and save.',
        'address_invalid' => 'This address does not exist or the postal code does not match it. Please check your street, house number, postal code and city.',
        'submit' => 'Save details',
        'saved' => 'Your details have been saved.',
    ],

    'password' => [
        'title' => 'Change password',
        'current' => 'Current password',
        'new' => 'New password',
        'confirm' => 'Confirm new password',
        'submit' => 'Change password',
        'saved' => 'Your password has been changed.',
    ],

    'delete' => [
        'title' => 'Delete account',
        'text' => 'Your personal data is permanently deleted: your name, email address, address and photos. Paid invoices are kept for seven years because tax law requires it, and are no longer linked to your account. This cannot be undone.',
        'password' => 'Confirm with your password',
        'submit' => 'Delete my account',
        'confirm' => 'Are you sure you want to permanently delete your account? Your personal data is lost; only the legally required invoice details are kept. This cannot be undone.',
        'removed' => 'Deleted account',
        'done' => 'Your account has been deleted. Your personal data has been erased.',
    ],
];
