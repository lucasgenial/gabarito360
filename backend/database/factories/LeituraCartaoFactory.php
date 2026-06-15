<?php

namespace Database\Factories;

use App\Enums\LeituraCartaoStatus;
use App\Models\Aplicacao;
use App\Models\AplicacaoAluno;
use App\Models\Arquivo;
use App\Models\LeituraCartao;
use App\Models\ModeloCartao;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LeituraCartao>
 */
class LeituraCartaoFactory extends Factory
{
    protected $model = LeituraCartao::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'aplicacao_id' => Aplicacao::factory(),
            'aplicacao_aluno_id' => AplicacaoAluno::factory(),
            'modelo_cartao_id' => ModeloCartao::factory(),
            'arquivo_original_id' => Arquivo::factory(),
            'capturada_por_id' => User::factory(),
            'operacao_id' => 'op-'.fake()->unique()->uuid(),
            'status' => LeituraCartaoStatus::RECEIVED->value,
            'confianca_geral' => 0.98,
            'requer_revisao' => false,
            'alertas' => null,
        ];
    }

    public function emRevisao(): self
    {
        return $this->state(fn (): array => [
            'status' => LeituraCartaoStatus::UNDER_REVIEW->value,
            'requer_revisao' => true,
            'confianca_geral' => 0.42,
            'alertas' => ['baixa_confianca'],
        ]);
    }
}
