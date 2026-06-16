<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EscolaSeeder extends Seeder
{
    public function run(): void
    {
        $nucleos = DB::table('nucleos')->pluck('id')->toArray();

        $escolas = [
            ['nucleo_id' => $nucleos[0], 'nome' => 'EMEF Prof. João Silva',       'inep' => '26000001', 'ativo' => true],
            ['nucleo_id' => $nucleos[0], 'nome' => 'EMEF Monteiro Lobato',        'inep' => '26000002', 'ativo' => true],
            ['nucleo_id' => $nucleos[1], 'nome' => 'EMEF Santos Dumont',          'inep' => '26000003', 'ativo' => true],
            ['nucleo_id' => $nucleos[1], 'nome' => 'EMEF Machado de Assis',       'inep' => '26000004', 'ativo' => true],
            ['nucleo_id' => $nucleos[2], 'nome' => 'EMEF Cecília Meireles',       'inep' => '26000005', 'ativo' => true],
            ['nucleo_id' => $nucleos[2], 'nome' => 'EMEF Pedro Álvares Cabral',   'inep' => '26000006', 'ativo' => false],
        ];

        foreach ($escolas as $escola) {
            DB::table('escolas')->insert(array_merge($escola, [
                'tipo_rede'  => 'municipal',
                'cidade'     => 'Cidade Exemplo',
                'uf'         => 'PE',
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }
}
