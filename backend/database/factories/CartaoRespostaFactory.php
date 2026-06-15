<?php

namespace Database\Factories;

use App\Models\Aluno;
use App\Models\Aplicacao;
use App\Models\CartaoResposta;
use App\Models\Prova;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CartaoResposta>
 */
class CartaoRespostaFactory extends Factory
{
    protected $model = CartaoResposta::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'prova_id' => Prova::factory(),
            'aluno_id' => Aluno::factory(),
            'aplicacao_id' => Aplicacao::factory(),
            'codigo_impresso' => null,
            'codigo_impresso_normalizado' => null,
            'codigo_sistema' => 'G360-'.fake()->unique()->regexify('[A-Z0-9]{12}').'-'.fake()->regexify('[A-Z0-9]'),
            'codigo_sistema_afixado' => false,
            'motivo_sem_codigo_impresso' => 'nao_detectado_na_captura',
            'status' => 'vigente',
        ];
    }
}
