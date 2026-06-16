<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class NucleoSeeder extends Seeder
{
    public function run(): void
    {
        $redeId = DB::table('redes')->value('id');

        DB::table('nucleos')->insert([
            ['rede_id' => $redeId, 'nome' => 'Núcleo Regional Norte', 'created_at' => now(), 'updated_at' => now()],
            ['rede_id' => $redeId, 'nome' => 'Núcleo Regional Sul',   'created_at' => now(), 'updated_at' => now()],
            ['rede_id' => $redeId, 'nome' => 'Núcleo Regional Leste', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}
