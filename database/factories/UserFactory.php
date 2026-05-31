<?php

namespace Database\Factories;

use App\Models\Department;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'username'           => fake()->unique()->userName(),
            'email'              => fake()->unique()->safeEmail(),
            'full_name'          => fake()->name(),
            'full_name_ar'       => null,
            'password'           => static::$password ??= Hash::make('password'),
            'department_id'      => Department::inRandomOrder()->first()?->id,
            'job_title'          => fake()->jobTitle(),
            'preferred_language' => fake()->randomElement(['en', 'ar']),
            'is_active'          => true,
            'remember_token'     => Str::random(10),
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }

    public function admin(): static
    {
        return $this->state(fn () => ['job_title' => 'Administrator']);
    }
}
