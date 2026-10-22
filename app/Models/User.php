<?php

namespace App\Models;

use App\Enums\UserRole;
use App\Notifications\ResetPasswordQueued;
use App\Notifications\VerifyEmailQueued;
use App\Support\MailDelivery;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

#[Fillable(['name', 'email', 'password', 'role', 'locale', 'street', 'postal_code', 'city'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements HasLocalePreference, MustVerifyEmail
{

    use HasFactory, Notifiable;

    public function preferredLocale(): ?string
    {
        return $this->locale;
    }

    /*
     * Op deze twee staat iemand te wachten met een half account of een vergeten
     * wachtwoord, dus ze gaan direct de deur uit. Zie MailDelivery voor waarom de
     * wachtrij hier alleen nog het vangnet is.
     */

    public function sendEmailVerificationNotification(): void
    {
        MailDelivery::send($this, new VerifyEmailQueued);
    }

    public function sendPasswordResetNotification($token): void
    {
        MailDelivery::send($this, new ResetPasswordQueued($token));
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
        ];
    }

    public function hostProfile(): HasOne
    {
        return $this->hasOne(HostProfile::class);
    }

    public function studios(): HasMany
    {
        return $this->hasMany(Studio::class);
    }

    public function rooms(): HasManyThrough
    {
        return $this->hasManyThrough(Room::class, Studio::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function isArtist(): bool
    {
        return $this->role === UserRole::Artiest;
    }

    public function isHost(): bool
    {
        return $this->role === UserRole::Verhuurder;
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function firstName(): string
    {
        return Str::before(trim($this->name), ' ');
    }

    public function hasCompleteAddress(): bool
    {
        return filled($this->street) && filled($this->postal_code) && filled($this->city);
    }
}
