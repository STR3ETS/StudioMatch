<?php

namespace App\Models;

use App\Enums\ExceptionType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['date', 'type', 'start_hour', 'end_hour', 'label'])]
class RoomException extends Model
{

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'type' => ExceptionType::class,
        ];
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }
}
