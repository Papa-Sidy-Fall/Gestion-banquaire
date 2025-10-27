<?php

namespace Database\Factories;

use App\Models\Compte;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class TransactionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'id' => (string) Str::uuid(),
            'compte_id' => Compte::factory(),
            'type' => $this->faker->randomElement(['Dépôt', 'Retrait', 'Transfert']),
            'montant' => $this->faker->randomFloat(2, 1000, 100000),
            'reference' => 'TX-' . strtoupper(Str::random(10)),
        ];
    }
}
