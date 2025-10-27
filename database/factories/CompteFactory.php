<?php

namespace Database\Factories;

use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CompteFactory extends Factory
{
    public function definition(): array
    {
        return [
            'id' => (string) Str::uuid(),
            'numero' => 'CPT-' . strtoupper(Str::random(8)),
            'client_id' => Client::factory(),
            'solde' => $this->faker->randomFloat(2, 500, 5000),
            'type_compte' => $this->faker->randomElement(['Courant', 'Épargne', 'Business']),
        ];
    }
}
