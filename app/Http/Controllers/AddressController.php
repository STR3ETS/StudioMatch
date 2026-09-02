<?php

namespace App\Http\Controllers;

use App\Support\Geocoder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AddressController extends Controller
{

    /**
     * Address suggestions from the Dutch address register, so people pick a real address
     * instead of typing one that does not exist.
     */
    public function suggest(Request $request): JsonResponse
    {
        $query = trim((string) $request->query('q'));

        if (mb_strlen($query) < 4) {
            return response()->json([]);
        }

        return response()->json(Geocoder::suggest($query));
    }
}
