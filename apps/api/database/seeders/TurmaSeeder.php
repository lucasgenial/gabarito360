<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TurmaSeeder extends Seeder
{
    public function run(): void
    {
        $escolaId = DB::table('escolas')->where('ativo', true)->value('id');

        $series = [
            ['nome' => '6º A', 'serie' => '6º ano'],
            ['nome' => '6º B', 'serie' => '6º ano'],
            ['nome' => '7º A', 'serie' => '7º ano'],
            ['nome' => '7º B', 'serie' => '7º ano'],
            ['nome' => '8º A', 'serie' => '8º ano'],
            ['nome' => '8º B', 'serie' => '8º ano'],
            ['nome' => '9º A', 'serie' => '9º ano'],
            ['nome' => '9º B', 'serie' => '9º ano'],
            ['nome' => '6º C', 'serie' => '6º ano'],
            ['nome' => '7º C', 'serie' => '7º ano'],
            ['nome' => '8º C', 'serie' => '8º ano'],
            ['nome' => '9º C', 'serie' => '9º ano'],
        ];

        foreach ($series as $serie) {
            DB::table('turmas')->insert([
                'escola_id'  => $escolaId,
                'nome'       => $serie['nome'],
                'serie'      => $serie['serie'],
                'turno'      => 'manha',
                'ano_letivo' => 2026,
                'ativo'      => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
