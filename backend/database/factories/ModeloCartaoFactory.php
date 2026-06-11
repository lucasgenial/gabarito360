<?php

namespace Database\Factories;

use App\Enums\ModeloCartaoOrigemCodigo;
use App\Enums\ModeloCartaoStatus;
use App\Enums\ModeloCartaoTipoCodigo;
use App\Models\ModeloCartao;
use App\Models\Nucleo;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ModeloCartao>
 */
class ModeloCartaoFactory extends Factory
{
    protected $model = ModeloCartao::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $version = 1;
        $alternatives = ['A', 'B', 'C', 'D', 'E'];

        return [
            'nucleo_id' => Nucleo::factory(),
            'nome' => fake()->unique()->bothify('Modelo OMR ####'),
            'versao' => $version,
            'quantidade_questoes' => 20,
            'quantidade_alternativas' => count($alternatives),
            'alternativas' => $alternatives,
            'tipo_codigo' => ModeloCartaoTipoCodigo::WITHOUT_CODE,
            'origem_codigo' => ModeloCartaoOrigemCodigo::NONE,
            'configuracao_omr' => self::configuration($alternatives),
            'artefato_checksum_sha256' => fake()->sha256(),
            'status' => ModeloCartaoStatus::DRAFT,
            'criado_por' => User::factory(),
            'homologado_por' => null,
            'homologado_at' => null,
        ];
    }

    /** @param list<string> $alternatives */
    public static function configuration(array $alternatives = ['A', 'B', 'C', 'D', 'E']): array
    {
        return [
            'spec_id' => 'gabarito360-cartao-omr-v1',
            'spec_version' => '1.0.0',
            'canvas' => ['width' => 2480, 'height' => 3508],
            'markers' => [
                'dictionary' => 'DICT_4X4_50',
                'items' => [
                    'TL' => ['id' => 0, 'region' => [120, 120, 140, 140]],
                    'TR' => ['id' => 1, 'region' => [2220, 120, 140, 140]],
                    'BR' => ['id' => 2, 'region' => [2220, 3248, 140, 140]],
                    'BL' => ['id' => 3, 'region' => [120, 3248, 140, 140]],
                ],
            ],
            'printed_code' => [
                'region' => [360, 250, 1760, 420],
                'type' => ModeloCartaoTipoCodigo::WITHOUT_CODE->value,
                'semantic_source' => ModeloCartaoOrigemCodigo::NONE->value,
                'normalization' => ['strategy' => 'none'],
            ],
            'answers' => [
                'questions' => 20,
                'alternatives' => $alternatives,
                'region' => [340, 850, 1800, 2200],
                'first_center_y' => 950,
                'row_step' => 105,
                'centers_x' => array_combine($alternatives, [900, 1120, 1340, 1560, 1780]),
            ],
            'thresholds' => [
                'preenchimento_minimo' => 0.65,
                'baixa_confianca' => 0.75,
                'dupla_marcacao' => 0.55,
            ],
        ];
    }
}
