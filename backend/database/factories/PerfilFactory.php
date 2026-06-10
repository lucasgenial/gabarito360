<?php

namespace Database\Factories;

use App\Enums\AccessScope;
use App\Enums\StatusEnum;
use App\Models\Perfil;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Perfil>
 */
class PerfilFactory extends Factory
{
    protected $model = Perfil::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'codigo' => fake()->unique()->bothify('perfil_########'),
            'nome' => fake()->words(2, true),
            'descricao' => fake()->sentence(),
            'escopo_permitido' => AccessScope::GLOBAL,
            'sistema' => false,
            'status' => StatusEnum::ACTIVE,
        ];
    }
}
