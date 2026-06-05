<?php

namespace Database\Factories;

use App\Enums\Role;
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
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'role' => Role::ExecutionEngineer,
            'project_id' => null,
            'sub_contractor_id' => null,
            'phone' => fake()->optional()->phoneNumber(),
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => Role::SystemAdmin,
        ]);
    }

    public function engineer(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => Role::ExecutionEngineer,
        ]);
    }

    public function siteManager(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => Role::SiteManager,
        ]);
    }

    public function permitOfficer(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => Role::PermitOfficer,
        ]);
    }

    public function workSupervisor(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => Role::WorkSupervisor,
        ]);
    }

    public function hseOfficer(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => Role::HseOfficer,
        ]);
    }

    public function consultant(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => Role::Consultant,
        ]);
    }

    public function qaInspector(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => Role::QaInspector,
        ]);
    }
}
