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
            ['perfil' => 'admin_rede',   'nome' => 'Admin da Rede',        'email' => 'admin@gabarito360.dev',       'cpf' => '00000000001'],
            ['perfil' => 'dir_nucleo',   'nome' => 'Diretor de Núcleo',    'email' => 'dir.nucleo@gabarito360.dev',  'cpf' => '00000000002'],
            ['perfil' => 'dir_escolar',  'nome' => 'Diretor Escolar',      'email' => 'dir.escolar@gabarito360.dev', 'cpf' => '00000000003'],
            ['perfil' => 'coordenador',  'nome' => 'Coordenador Pedagógico','email' => 'coordenador@gabarito360.dev','cpf' => '00000000004'],
            ['perfil' => 'professor',    'nome' => 'Professor Exemplo',    'email' => 'professor@gabarito360.dev',   'cpf' => '00000000005'],
            ['perfil' => 'aluno',        'nome' => 'Aluno Exemplo',        'email' => 'aluno@gabarito360.dev',       'cpf' => '00000000006'],
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
