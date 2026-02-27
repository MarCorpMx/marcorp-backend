<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ClientFactory extends Factory
{
    public function definition(): array
    {
        return [
            'first_name' => fake()->firstName(),
            'last_name'  => fake()->optional()->lastName(),

            'phone' => [
                'mobile' => fake()->phoneNumber(),
            ],

            // 👇 Forma segura
            'email' => fake()->boolean(70)
                ? fake()->unique()->safeEmail()
                : null,

            'notes' => fake()->optional()->sentence(),

            'is_active' => true,
        ];
    }
}
