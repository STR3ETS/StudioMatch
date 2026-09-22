<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

#[Fillable(['title', 'body', 'sort_order'])]
class PostSection extends Model
{
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    /** Id om vanuit de inhoudsopgave naartoe te springen. */
    public function anchor(): string
    {
        return Str::slug((string) $this->title) ?: 'alinea-' . ((int) $this->sort_order + 1);
    }

    public function isEmpty(): bool
    {
        return trim($this->title ?? '') === '' && trim(strip_tags($this->body ?? '')) === '';
    }
}
