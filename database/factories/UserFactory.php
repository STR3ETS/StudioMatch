<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserFactory extends Factory
{

    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name' => fake()->firstName() . ' ' . fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'street' => fake()->streetName() . ' ' . fake()->buildingNumber(),
            'postal_code' => fake()->numberBetween(1000, 9999) . ' ' . strtoupper(fake()->lexify('??')),
            'city' => fake()->city(),
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function withoutAddress(): static
    {
        return $this->state(fn (array $attributes) => [
            'street' => null,
            'postal_code' => null,
            'city' => null,
        ]);
    }
}
