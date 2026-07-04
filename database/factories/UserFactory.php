<?php

namespace Database\Factories;

use App\Models\Sucursal;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'sucursal_id' => Sucursal::factory(),
            'is_super_admin' => false,
            'activo' => true,
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn () => ['email_verified_at' => null]);
    }

    public function superAdmin(): static
    {
        return $this->state(fn () => ['is_super_admin' => true, 'sucursal_id' => null]);
    }

    public function sinSucursal(): static
    {
        return $this->state(fn () => ['sucursal_id' => null]);
    }
}
