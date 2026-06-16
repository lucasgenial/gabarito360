<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RedeSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('redes')->insert([
            'nome'             => 'Rede Municipal de Ensino de Exemplo',
            'tipo'             => 'municipal',
            'uf'               => 'PE',
            'meta_media'       => 7.00,
            'meta_minima'      => 6.00,
            'limiar_seges_min' => 30,
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);
    }
}
