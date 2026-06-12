<?php

namespace Database\Seeders;

use App\Models\Cargo;
use App\Models\Disciplina;
use App\Models\SerieAno;
use Illuminate\Database\Seeder;

class AcademicCatalogSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->roles() as $code => $name) {
            Cargo::query()->updateOrCreate(
                ['codigo' => $code],
                ['nome' => $name, 'ativo' => true],
            );
        }

        foreach ($this->grades() as $order => $name) {
            SerieAno::query()->updateOrCreate(
                ['codigo' => $order.'-ano'],
                ['nome' => $name, 'ordem' => $order, 'nivel' => 'fundamental', 'ativo' => true],
            );
        }

        foreach ($this->subjects() as $code => $name) {
            Disciplina::query()->updateOrCreate(
                ['codigo' => $code],
                ['nome' => $name, 'ativo' => true],
            );
        }
    }

    /** @return array<string, string> */
    private function roles(): array
    {
        return [
            'diretor-nucleo' => 'Diretor de Nucleo',
            'diretor-escolar' => 'Diretor Escolar',
            'vice-diretor' => 'Vice-diretor',
            'coordenador-pedagogico' => 'Coordenador Pedagogico',
            'professor' => 'Professor',
            'aplicador' => 'Aplicador',
        ];
    }

    /** @return array<int, string> */
    private function grades(): array
    {
        return [
            1 => '1 ano',
            2 => '2 ano',
            3 => '3 ano',
            4 => '4 ano',
            5 => '5 ano',
            6 => '6 ano',
            7 => '7 ano',
            8 => '8 ano',
            9 => '9 ano',
        ];
    }

    /** @return array<string, string> */
    private function subjects(): array
    {
        return [
            'arte' => 'Arte',
            'biologia' => 'Biologia',
            'ciencias' => 'Ciencias',
            'educacao-fisica' => 'Educacao Fisica',
            'espanhol' => 'Espanhol',
            'filosofia' => 'Filosofia',
            'fisica' => 'Fisica',
            'geografia' => 'Geografia',
            'historia' => 'Historia',
            'ingles' => 'Ingles',
            'matematica' => 'Matematica',
            'portugues' => 'Portugues',
            'quimica' => 'Quimica',
            'sociologia' => 'Sociologia',
        ];
    }
}
