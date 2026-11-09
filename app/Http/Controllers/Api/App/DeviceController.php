<?php

namespace App\Http\Controllers\Api\App;

use App\Http\Controllers\Controller;
use App\Models\DeviceToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * De app meldt hier het Firebase-token van de telefoon aan (mobile/app/main.js), zodat pushmeldingen
 * bij de ingelogde gebruiker aankomen. Bij uitloggen gaat het token weer weg (SessionController::destroy).
 */
class DeviceController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string', 'max:512'],
            'platform' => ['required', 'in:android,ios'],
        ]);

        // Hetzelfde toestel met een andere gebruiker: het token verhuist mee.
        DeviceToken::updateOrCreate(
            ['token' => $validated['token']],
            ['user_id' => $request->user()->id, 'platform' => $validated['platform']],
        )->touch();

        return response()->json(['registered' => true]);
    }
}
