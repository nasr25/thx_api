<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AppreciationFactory extends Factory
{
    public function definition(): array
    {
        $users = User::pluck('id')->toArray();
        $sender   = fake()->randomElement($users);
        $receiver = fake()->randomElement(array_filter($users, fn ($id) => $id !== $sender));

        return [
            'sender_id'   => $sender,
            'receiver_id' => $receiver,
            'message'     => fake()->boolean(70) ? fake()->sentence(rand(5, 20)) : null,
            'is_public'   => fake()->boolean(90),
            'created_at'  => fake()->dateTimeBetween('-3 months', 'now'),
        ];
    }
}
