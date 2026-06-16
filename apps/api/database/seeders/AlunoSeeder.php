<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;

class AlunoSeeder extends Seeder
{
    public function run(): void
    {
        $faker  = Faker::create('pt_BR');
        $turmas = DB::table('turmas')->pluck('id');
        $count  = 0;

        foreach ($turmas as $turmaId) {
            for ($i = 1; $i <= 30; $i++) {
                $count++;
                DB::table('alunos')->insert([
                    'turma_id'         => $turmaId,
                    'nome'             => $faker->name(),
                    'matricula'        => str_pad((string) $count, 8, '0', STR_PAD_LEFT),
                    'data_nascimento'  => $faker->dateTimeBetween('-16 years', '-10 years')->format('Y-m-d'),
                    'nome_responsavel' => $faker->name(),
                    'ativo'            => true,
                    'created_at'       => now(),
                    'updated_at'       => now(),
                ]);
            }
        }
    }
}
