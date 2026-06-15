<?php

namespace Database\Factories;

use App\Models\Arquivo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Arquivo>
 */
class ArquivoFactory extends Factory
{
    protected $model = Arquivo::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $caminho = 'leituras/'.fake()->uuid().'.jpg';

        return [
            'disco' => 'local',
            'caminho' => $caminho,
            'nome_original' => 'cartao.jpg',
            'mime' => 'image/jpeg',
            'tamanho_bytes' => fake()->numberBetween(20000, 800000),
            'checksum' => hash('sha256', $caminho),
            'classificacao' => 'interno',
            'proprietario_tipo' => 'leitura_cartao',
            'proprietario_id' => null,
            'criado_por_id' => null,
        ];
    }
}
