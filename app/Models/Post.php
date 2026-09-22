<?php

namespace App\Models;

use App\Enums\PostStatus;
use App\Support\RichText;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

#[Fillable(['title', 'slug', 'excerpt', 'cover_path', 'status', 'published_at'])]
class Post extends Model
{
    protected static function booted(): void
    {

        static::saving(function (Post $post) {
            $post->slug = static::uniqueSlug($post->slug ?: $post->title, $post->id);
        });

        static::deleted(function (Post $post) {
            if ($post->cover_path) {
                Storage::disk('public')->delete($post->cover_path);
            }
        });
    }

    protected function casts(): array
    {
        return [
            'status' => PostStatus::class,
            'published_at' => 'datetime',
        ];
    }

    public function sections(): HasMany
    {
        return $this->hasMany(PostSection::class)->orderBy('sort_order');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Live on the public site: published and not scheduled for a future date.
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', PostStatus::Gepubliceerd)
            ->where(fn (Builder $q) => $q->whereNull('published_at')->orWhere('published_at', '<=', now()));
    }

    public function isPublished(): bool
    {
        return $this->status === PostStatus::Gepubliceerd
            && ($this->published_at === null || $this->published_at->isPast());
    }

    public function isScheduled(): bool
    {
        return $this->status === PostStatus::Gepubliceerd
            && $this->published_at !== null
            && $this->published_at->isFuture();
    }

    public function coverUrl(): ?string
    {
        return $this->cover_path ? Storage::disk('public')->url($this->cover_path) : null;
    }

    /**
     * Roughly 200 words per minute over the section bodies, for the reading-time hint.
     */
    public function readingMinutes(): int
    {
        $text = RichText::plain($this->sections->pluck('body')->implode(' '));
        $words = $text === '' ? [] : preg_split('/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY);

        return max(1, (int) ceil(count($words) / 200));
    }

    /**
     * De samenvatting als die er is, anders de eerste zinnen van het artikel zelf,
     * zodat de meta-omschrijving nooit leeg naar Google gaat.
     */
    public function metaDescription(): string
    {
        $text = trim((string) $this->excerpt);

        if ($text === '') {
            $text = RichText::plain($this->sections->pluck('body')->implode(' '));
        }

        return Str::limit($text, 155);
    }

    private static function uniqueSlug(string $source, ?int $ignoreId): string
    {
        $base = Str::slug($source) ?: 'blog';
        $slug = $base;
        $suffix = 2;

        while (static::where('slug', $slug)->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))->exists()) {
            $slug = $base . '-' . $suffix++;
        }

        return $slug;
    }
}
