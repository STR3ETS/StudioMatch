<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

// De locatie-laag: een verhuurder kan meerdere studio's hebben, elk met eigen adres.
#[Fillable(['name', 'phone', 'street', 'postal_code', 'city', 'lat', 'lng'])]
class Studio extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'lat' => 'float',
            'lng' => 'float',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class);
    }

    public function fullAddress(): string
    {
        return $this->street . ', ' . $this->postal_code . ' ' . $this->city;
    }
}
