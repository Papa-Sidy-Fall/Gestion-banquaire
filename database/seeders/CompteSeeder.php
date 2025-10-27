<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Compte;

class CompteSeeder extends Seeder
{
    public function run(): void
    {
        Compte::factory(10)->create();
    }
}
