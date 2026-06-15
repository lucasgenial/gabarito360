<?php

namespace Database\Factories;

use App\Models\LeituraCartao;
use App\Models\Questao;
use App\Models\RespostaDetectada;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RespostaDetectada>
 */
class RespostaDetectadaFactory extends Factory
{
    protected $model = RespostaDetectada::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'leitura_cartao_id' => LeituraCartao::factory(),
            'questao_id' => Questao::factory(),
            'alternativa_detectada' => 'A',
            'alternativa_final' => 'A',
            'tipo_deteccao' => 'marcada',
            'confianca' => 0.97,
        ];
    }

    public function marcada(string $alternativa = 'A'): self
    {
        return $this->state(fn (): array => [
            'alternativa_detectada' => $alternativa,
            'alternativa_final' => $alternativa,
            'tipo_deteccao' => 'marcada',
            'confianca' => 0.97,
        ]);
    }

    public function ambigua(): self
    {
        return $this->state(fn (): array => [
            'alternativa_detectada' => null,
            'alternativa_final' => null,
            'tipo_deteccao' => 'ambigua',
            'confianca' => 0.38,
        ]);
    }

    public function branco(): self
    {
        return $this->state(fn (): array => [
            'alternativa_detectada' => null,
            'alternativa_final' => null,
            'tipo_deteccao' => 'branco',
            'confianca' => 0.10,
        ]);
    }
}
