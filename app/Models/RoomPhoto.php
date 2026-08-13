<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

#[Fillable(['path', 'thumb_path', 'sort_order'])]
class RoomPhoto extends Model
{
    protected static function booted(): void
    {

        static::deleted(function (RoomPhoto $photo) {
            Storage::disk('public')->delete(array_filter([$photo->path, $photo->thumb_path]));
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

    public function thumbUrl(): string
    {
        return Storage::disk('public')->url($this->thumb_path ?? $this->path);
    }
}
