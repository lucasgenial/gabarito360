<?php

namespace Database\Factories;

use App\Models\Permissao;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Permissao>
 */
class PermissaoFactory extends Factory
{
    protected $model = Permissao::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'codigo' => fake()->unique()->bothify('recurso_########.consultar'),
            'descricao' => fake()->sentence(),
        ];
    }
}
