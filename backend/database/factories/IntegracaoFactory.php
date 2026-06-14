<?php

namespace Database\Factories;

use App\Enums\IntegracaoStatus;
use App\Models\Integracao;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Integracao>
 */
class IntegracaoFactory extends Factory
{
    protected $model = Integracao::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'escopo' => 'global',
            'nucleo_id' => null,
            'escola_id' => null,
            'chave' => Str::slug(fake()->unique()->words(2, true), '_'),
            'nome' => fake()->company(),
            'descricao' => fake()->sentence(),
            'status' => IntegracaoStatus::DISCONNECTED,
            'ativa' => true,
        ];
    }

    public function conectada(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => IntegracaoStatus::CONNECTED,
            'ultima_execucao' => now(),
        ]);
    }
}
