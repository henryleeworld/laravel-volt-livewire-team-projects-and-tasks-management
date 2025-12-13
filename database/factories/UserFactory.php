<?php

namespace Database\Factories;

use App\Enums\RoleEnum;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'email_notifications' => true,
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'two_factor_secret' => Str::random(10),
            'two_factor_recovery_codes' => Str::random(10),
            'two_factor_confirmed_at' => now(),
            'organization_id' => Organization::factory(),
        ];
    }

    public function asAdmin(): static
    {
        return $this->afterCreating(function (User $user) {
            $user->syncRoles([RoleEnum::Admin]);
        });
    }

    public function asUser(): static
    {
        return $this->afterCreating(function (User $user) {
            $user->syncRoles([RoleEnum::User]);
        });
    }

    public function asViewer(): static
    {
        return $this->afterCreating(function (User $user) {
            $user->syncRoles([RoleEnum::Viewer]);
        });
    }

    /**
     * Configure the factory.
     *
     * @return static
     */
    public function configure(): static
    {
        return $this->afterCreating(function (User $user) {
            if (! $user->roles()->exists()) {
                $user->assignRole(RoleEnum::Admin);
            }
        });
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Indicate that the model does not have two-factor authentication configured.
     */
    public function withoutTwoFactor(): static
    {
        return $this->state(fn (array $attributes) => [
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ]);
    }

    public function withProjectAccess(): static
    {
        return $this->state(fn (array $attributes) => [
            'organization_id' => Organization::factory()->withProSubscription(),
        ]);
    }
}
