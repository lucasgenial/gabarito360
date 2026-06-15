<?php

namespace Database\Factories;

use App\Enums\AplicacaoStatus;
use App\Models\Aplicacao;
use App\Models\Escola;
use App\Models\GabaritoOficial;
use App\Models\Prova;
use App\Models\Turma;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Aplicacao>
 */
class AplicacaoFactory extends Factory
{
    protected $model = Aplicacao::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'prova_id' => Prova::factory(),
            'turma_id' => Turma::factory(),
            'escola_id' => Escola::factory(),
            'gabarito_oficial_id' => GabaritoOficial::factory(),
            'titulo' => fake()->sentence(3),
            'inicio_previsto_at' => now(),
            'fim_previsto_at' => now()->addHours(2),
            'status' => AplicacaoStatus::SCHEDULED->value,
            'criada_por_id' => User::factory(),
        ];
    }

    public function emAndamento(): self
    {
        return $this->state(fn (): array => [
            'status' => AplicacaoStatus::IN_PROGRESS->value,
            'iniciada_at' => now(),
        ]);
    }

    public function finalizada(): self
    {
        return $this->state(fn (): array => [
            'status' => AplicacaoStatus::FINISHED->value,
            'iniciada_at' => now()->subHour(),
            'finalizada_at' => now(),
        ]);
    }
}
