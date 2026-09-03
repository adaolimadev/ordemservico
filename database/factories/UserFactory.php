<?php

namespace Database\Factories;

use App\Enums\PerfilEnum;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name'              => fake()->name(),
            'email'             => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password'          => static::$password ??= Hash::make('password'),
            'remember_token'    => Str::random(10),
            'cargo'             => fake()->jobTitle(),
            'situacao'          => true,
            'perfil'            => PerfilEnum::ATENDENTE,
        ];
    }

    /**
     * Usuário com e-mail não verificado.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Usuário com perfil Administrador.
     */
    public function administrador(): static
    {
        return $this->state(fn (array $attributes) => [
            'perfil' => PerfilEnum::ADMINISTRADOR,
        ]);
    }

    /**
     * Usuário inativo (situacao = false).
     */
    public function inativo(): static
    {
        return $this->state(fn (array $attributes) => [
            'situacao' => false,
        ]);
    }
}
