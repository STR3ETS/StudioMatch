<?php

use App\Http\Controllers\Api\App\SessionController;

/*
 * Alleen de app-API (api/app/*) mag van buiten worden aangeroepen, en alleen door de app zelf: die draait
 * op https://localhost (Android), capacitor://localhost (iOS) of http://localhost (Android tegen een
 * http-testserver). Geen cookies: de app logt in met een token.
 */
return [

    'paths' => ['api/app/*'],

    'allowed_methods' => ['*'],

    'allowed_origins' => SessionController::APP_ORIGINS,

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 3600,

    'supports_credentials' => false,

];
