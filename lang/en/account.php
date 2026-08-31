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
        'confirm' => 'Repeat new password',
        'submit' => 'Change password',
        'saved' => 'Your password has been changed.',
    ],

    'delete' => [
        'title' => 'Delete account',
        'text' => 'Your account and all associated data will be permanently deleted, in accordance with the GDPR. This cannot be undone.',
        'password' => 'Confirm with your password',
        'submit' => 'Delete my account',
        'confirm' => 'Are you sure you want to permanently delete your account? All your data will be lost and this cannot be undone.',
    ],
];
