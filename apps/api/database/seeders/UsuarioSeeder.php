<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UsuarioSeeder extends Seeder
{
    public function run(): void
    {
        $usuarios = [
            ['perfil' => 'admin_rede',   'nome' => 'Admin da Rede',          'email' => 'admin@gabarito360.dev',       'cpf' => '00000000001', 'escola_nome' => null,                       'ultimo_acesso' => now()->subMinutes(5)],
            ['perfil' => 'dir_nucleo',   'nome' => 'Bianca Ramos',           'email' => 'dir.nucleo@gabarito360.dev',  'cpf' => '00000000002', 'escola_nome' => 'Núcleo Regional Norte',    'ultimo_acesso' => now()->subHours(2)],
            ['perfil' => 'dir_escolar',  'nome' => 'Mariana Costa',          'email' => 'dir.escolar@gabarito360.dev', 'cpf' => '00000000003', 'escola_nome' => 'EMEF Prof. João Silva',    'ultimo_acesso' => now()->subMinutes(20)],
            ['perfil' => 'coordenador',  'nome' => 'Renata Nogueira',        'email' => 'coordenador@gabarito360.dev', 'cpf' => '00000000004', 'escola_nome' => 'EMEF Monteiro Lobato',     'ultimo_acesso' => now()->subHours(1)],
            ['perfil' => 'professor',    'nome' => 'Paulo Mendes',           'email' => 'professor@gabarito360.dev',   'cpf' => '00000000005', 'escola_nome' => 'EMEF Machado de Assis',   'ultimo_acesso' => now()->subHours(3)],
            ['perfil' => 'aluno',        'nome' => 'Lucas Teixeira',         'email' => 'aluno@gabarito360.dev',       'cpf' => '00000000006', 'escola_nome' => 'EMEF Santos Dumont',      'ultimo_acesso' => now()->subHours(5)],
        ];

        foreach ($usuarios as $usuario) {
            DB::table('usuarios')->insert(array_merge($usuario, [
                'password'   => Hash::make('password'),
                'ativo'      => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }
}
