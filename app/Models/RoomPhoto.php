<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

#[Fillable(['path', 'sort_order'])]
class RoomPhoto extends Model
{
    protected static function booted(): void
    {
        // Verwijder het bestand mee zodra de databaserij verdwijnt.
        static::deleted(function (RoomPhoto $photo) {
            Storage::disk('public')->delete($photo->path);
        });
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function url(): string
    {
        return Storage::disk('public')->url($this->path);
    }
}
