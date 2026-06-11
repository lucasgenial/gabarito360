<?php

namespace Database\Factories;

use App\Enums\GabaritoOficialStatus;
use App\Models\GabaritoOficial;
use App\Models\Prova;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GabaritoOficial>
 */
class GabaritoOficialFactory extends Factory
{
    protected $model = GabaritoOficial::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'prova_id' => Prova::factory(),
            'versao' => 1,
            'status' => GabaritoOficialStatus::DRAFT,
            'justificativa' => null,
            'criado_por' => User::factory(),
            'publicado_por' => null,
            'publicado_at' => null,
        ];
    }
}
