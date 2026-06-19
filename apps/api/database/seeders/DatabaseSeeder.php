<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            PlanoSeeder::class,
            RedeSeeder::class,
            NucleoSeeder::class,
            EscolaSeeder::class,
            TurmaSeeder::class,
            AlunoSeeder::class,
            UsuarioSeeder::class,
        ]);
    }
}
