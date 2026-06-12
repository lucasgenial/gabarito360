<?php

namespace Database\Seeders;

use App\Actions\Provas\LinkProvaTurmaAction;
use App\Actions\Provas\PublishProva;
use App\Enums\GabaritoOficialStatus;
use App\Enums\MatriculaTurmaStatus;
use App\Enums\ModeloCartaoOrigemCodigo;
use App\Enums\ModeloCartaoStatus;
use App\Enums\ModeloCartaoTipoCodigo;
use App\Enums\ProvaStatus;
use App\Enums\QuestaoStatus;
use App\Enums\StatusEnum;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Aluno;
use App\Models\Escola;
use App\Models\GabaritoOficial;
use App\Models\GabaritoResposta;
use App\Models\MatriculaTurma;
use App\Models\ModeloCartao;
use App\Models\Nucleo;
use App\Models\Perfil;
use App\Models\Prova;
use App\Models\Questao;
use App\Models\Turma;
use App\Models\User;
use App\Models\UsuarioPerfil;
use Database\Factories\ModeloCartaoFactory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use LogicException;

class LocalDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local')) {
            throw new LogicException('Os dados de demonstracao somente podem ser criados no ambiente local.');
        }

        $this->call(AccessControlSeeder::class);

        DB::transaction(function (): void {
            $admin = User::query()->updateOrCreate(
                ['email' => 'admin@gabarito360.local'],
                [
                    'nome' => 'Administrador Local',
                    'password' => 'Gabarito360@Local',
                    'status' => UserStatus::ACTIVE,
                ],
            );

            $profile = Perfil::query()
                ->where('codigo', UserRole::ADMINISTRATOR->value)
                ->firstOrFail();

            UsuarioPerfil::query()->firstOrCreate(
                [
                    'usuario_id' => $admin->id,
                    'perfil_id' => $profile->id,
                    'nucleo_id' => null,
                    'escola_id' => null,
                    'fim_at' => null,
                ],
                [
                    'concedido_por' => $admin->id,
                    'inicio_at' => now(),
                ],
            );

            $nucleo = Nucleo::query()->updateOrCreate(
                ['codigo' => 'NUC-DEMO'],
                [
                    'nome' => 'Nucleo Educacional Demonstracao',
                    'municipio' => 'Brasilia',
                    'estado' => 'DF',
                    'email' => 'nucleo@gabarito360.local',
                    'telefone' => '(61) 3000-3600',
                    'status' => StatusEnum::ACTIVE,
                ],
            );

            $escola = Escola::query()->updateOrCreate(
                ['nucleo_id' => $nucleo->id, 'codigo' => 'ESC-DEMO'],
                [
                    'nome' => 'Escola Municipal Demonstracao',
                    'municipio' => 'Brasilia',
                    'estado' => 'DF',
                    'endereco' => [
                        'logradouro' => 'Endereco ficticio para demonstracao',
                        'numero' => '360',
                    ],
                    'email' => 'escola@gabarito360.local',
                    'telefone' => '(61) 3000-3601',
                    'status' => StatusEnum::ACTIVE,
                ],
            );

            $turma = Turma::query()->updateOrCreate(
                [
                    'escola_id' => $escola->id,
                    'codigo' => '6A-DEMO',
                    'ano_letivo' => 2026,
                ],
                [
                    'nome' => '6 Ano A - Demonstracao',
                    'serie_ano' => '6 ano',
                    'turno' => 'matutino',
                    'status' => StatusEnum::ACTIVE,
                ],
            );

            foreach ($this->students() as $index => $studentData) {
                $student = Aluno::query()->updateOrCreate(
                    [
                        'escola_id' => $escola->id,
                        'matricula' => $studentData['matricula'],
                    ],
                    [
                        'nome' => $studentData['nome'],
                        'codigo_interno' => $studentData['codigo_interno'],
                        'status' => StatusEnum::ACTIVE,
                    ],
                );

                MatriculaTurma::query()->firstOrCreate(
                    [
                        'aluno_id' => $student->id,
                        'ano_letivo' => 2026,
                        'status' => MatriculaTurmaStatus::ACTIVE,
                    ],
                    [
                        'turma_id' => $turma->id,
                        'numero_chamada' => (string) ($index + 1),
                        'inicio_em' => '2026-02-02',
                    ],
                );
            }

            $this->createPublishedExam($admin, $nucleo, $turma);
        });
    }

    private function createPublishedExam(User $admin, Nucleo $nucleo, Turma $turma): void
    {
        if (Prova::query()->where('nucleo_id', $nucleo->id)->where('codigo', 'PROVA-DEMO-2026')->exists()) {
            return;
        }

        $alternatives = ['A', 'B', 'C', 'D', 'E'];

        $cardModel = ModeloCartao::query()->firstOrCreate(
            ['nucleo_id' => null, 'nome' => 'Cartao OMR Demonstracao', 'versao' => 1],
            [
                'quantidade_questoes' => 20,
                'quantidade_alternativas' => count($alternatives),
                'alternativas' => $alternatives,
                'tipo_codigo' => ModeloCartaoTipoCodigo::QR_CODE,
                'origem_codigo' => ModeloCartaoOrigemCodigo::EXTERNAL,
                'configuracao_omr' => ModeloCartaoFactory::configuration($alternatives),
                'artefato_checksum_sha256' => hash('sha256', 'gabarito360-modelo-demonstracao-v1'),
                'status' => ModeloCartaoStatus::APPROVED,
                'criado_por' => $admin->id,
                'homologado_por' => $admin->id,
                'homologado_at' => now(),
            ],
        );

        $exam = Prova::query()->create([
            'nucleo_id' => $nucleo->id,
            'escola_id' => null,
            'modelo_cartao_id' => $cardModel->id,
            'codigo' => 'PROVA-DEMO-2026',
            'titulo' => 'Avaliacao Diagnostica de Demonstracao',
            'descricao' => 'Prova ficticia criada exclusivamente para visualizar o painel local.',
            'tipo' => 'diagnostico',
            'nivel' => '6 ano',
            'ano_referencia' => 2026,
            'quantidade_questoes' => 20,
            'quantidade_alternativas' => count($alternatives),
            'alternativas' => $alternatives,
            'status' => ProvaStatus::DRAFT,
            'criado_por' => $admin->id,
        ]);

        $answerKey = GabaritoOficial::query()->create([
            'prova_id' => $exam->id,
            'versao' => 1,
            'status' => GabaritoOficialStatus::DRAFT,
            'criado_por' => $admin->id,
        ]);

        foreach (range(1, 20) as $number) {
            $question = Questao::query()->create([
                'prova_id' => $exam->id,
                'numero' => $number,
                'codigo' => sprintf('Q-%02d', $number),
                'peso_padrao' => 1,
                'status' => QuestaoStatus::ACTIVE,
            ]);

            GabaritoResposta::query()->create([
                'prova_id' => $exam->id,
                'gabarito_oficial_id' => $answerKey->id,
                'questao_id' => $question->id,
                'alternativa_correta' => $alternatives[($number - 1) % count($alternatives)],
                'anulada' => false,
                'peso' => 1,
            ]);
        }

        app(PublishProva::class)->execute($exam, $answerKey, $admin);
        app(LinkProvaTurmaAction::class)->execute(
            $exam->refresh(),
            $turma,
            ['data_prevista' => '2026-06-15'],
            $admin,
        );
    }

    /** @return list<array{matricula: string, codigo_interno: string, nome: string}> */
    private function students(): array
    {
        return [
            ['matricula' => 'DEMO-001', 'codigo_interno' => 'G360-DEMO-001-C', 'nome' => 'Ana Demonstracao'],
            ['matricula' => 'DEMO-002', 'codigo_interno' => 'G360-DEMO-002-C', 'nome' => 'Bruno Demonstracao'],
            ['matricula' => 'DEMO-003', 'codigo_interno' => 'G360-DEMO-003-C', 'nome' => 'Carla Demonstracao'],
            ['matricula' => 'DEMO-004', 'codigo_interno' => 'G360-DEMO-004-C', 'nome' => 'Diego Demonstracao'],
            ['matricula' => 'DEMO-005', 'codigo_interno' => 'G360-DEMO-005-C', 'nome' => 'Elisa Demonstracao'],
        ];
    }
}
