<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ClientFactory extends Factory
{
    public function definition(): array
    {
        $countryCode = 'MX';
        $dialCode = '+52';

        $national = fake()->numerify('55########');
        $e164 = $dialCode . $national;

        return [
            'first_name' => fake()->firstName(),
            'last_name'  => fake()->optional()->lastName(),

            'phone' => [
                'number' => $e164,
                'internationalNumber' => $dialCode . ' ' . $national,
                'nationalNumber' => $national,
                'e164Number' => $e164,
                'countryCode' => $countryCode,
                'dialCode' => $dialCode,
            ],

            'email' => fake()->boolean(70)
                ? fake()->unique()->safeEmail()
                : null,

            'notes' => fake()->optional()->sentence(),

            'is_active' => true,
        ];
    }
}
