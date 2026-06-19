<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PlanoSeeder extends Seeder
{
    public function run(): void
    {
        $planos = [
            [
                'nome'              => 'Professor Individual — Mensal',
                'publico'           => 'individual',
                'modo'              => 'autoatendimento',
                'preco_fixo'        => 9.99,
                'preco_por_licenca' => null,
                'licencas_min'      => 1,
                'licencas_max'      => 1,
                'periodicidade'     => 'mensal',
                'limites'           => json_encode(['escolas' => 1, 'turmas' => 20, 'provas_mes' => 50]),
                'ativo'             => true,
            ],
            [
                'nome'              => 'Professor Individual — Anual',
                'publico'           => 'individual',
                'modo'              => 'autoatendimento',
                'preco_fixo'        => 99.90,
                'preco_por_licenca' => null,
                'licencas_min'      => 1,
                'licencas_max'      => 1,
                'periodicidade'     => 'anual',
                'limites'           => json_encode(['escolas' => 1, 'turmas' => 20, 'provas_mes' => 50]),
                'ativo'             => true,
            ],
            [
                'nome'              => 'Instituição — até 10 licenças',
                'publico'           => 'institucional',
                'modo'              => 'autoatendimento',
                'preco_fixo'        => null,
                'preco_por_licenca' => 7.50,
                'licencas_min'      => 1,
                'licencas_max'      => 10,
                'periodicidade'     => 'mensal',
                'limites'           => json_encode(['escolas' => 5, 'turmas' => 100, 'provas_mes' => 200]),
                'ativo'             => true,
            ],
            [
                'nome'              => 'Instituição — mais de 10 licenças',
                'publico'           => 'institucional',
                'modo'              => 'comercial',
                'preco_fixo'        => null,
                'preco_por_licenca' => null,
                'licencas_min'      => 11,
                'licencas_max'      => null,
                'periodicidade'     => null,
                'limites'           => null,
                'ativo'             => true,
            ],
            [
                'nome'              => 'Secretaria / Núcleos',
                'publico'           => 'institucional',
                'modo'              => 'comercial',
                'preco_fixo'        => null,
                'preco_por_licenca' => null,
                'licencas_min'      => null,
                'licencas_max'      => null,
                'periodicidade'     => null,
                'limites'           => null,
                'ativo'             => true,
            ],
        ];

        foreach ($planos as $plano) {
            DB::table('planos')->updateOrInsert(['nome' => $plano['nome']], $plano);
        }
    }
}
