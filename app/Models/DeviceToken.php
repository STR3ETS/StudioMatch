<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Firebase-token van een telefoon (iOS/Android-app) waarop de gebruiker is ingelogd. Eén token hoort
 * bij één gebruiker: wie op dezelfde telefoon inlogt, neemt het over.
 */
class DeviceToken extends Model
{
    protected $fillable = ['user_id', 'token', 'platform'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
