<?php

namespace Database\Seeders\Support;

use App\Actions\Aplicacoes\CreateAplicacaoAction;
use App\Actions\Aplicacoes\StartAplicacaoAction;
use App\Actions\Leituras\CreatePreliminaryReadingAction;
use App\Enums\GabaritoOficialStatus;
use App\Enums\ModeloCartaoStatus;
use App\Enums\ProvaStatus;
use App\Models\Aplicacao;
use App\Models\AplicacaoAluno;
use App\Models\AplicacaoAplicador;
use App\Models\Arquivo;
use App\Models\Escola;
use App\Models\GabaritoOficial;
use App\Models\GabaritoResposta;
use App\Models\LeituraCartao;
use App\Models\MatriculaTurma;
use App\Models\ModeloCartao;
use App\Models\Prova;
use App\Models\ProvaTurma;
use App\Models\Questao;
use App\Models\Turma;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Monta o grafo operacional completo (prova publicada com modelo de cartão,
 * gabarito vigente, turma vinculada, matrículas, aplicação e leituras),
 * reutilizado pelo seeder de demonstração e pelos testes de feature.
 */
class OperationsScenarioBuilder
{
    private const ALTERNATIVES = ['A', 'B', 'C', 'D', 'E'];

    /**
     * Prova PUBLICADA (com modelo de cartão), gabarito vigente + respostas,
     * questões 1..N, turma vinculada e matrículas ativas.
     *
     * @param  array{questoes?: int, alunos?: int}  $options
     * @return array{modelo: ModeloCartao, prova: Prova, gabarito: GabaritoOficial, questoes: Collection<int, Questao>, turma: Turma, matriculas: Collection<int, MatriculaTurma>}
     */
    public function publishedExamWithClass(Escola $escola, User $author, array $options = []): array
    {
        $numQuestoes = $options['questoes'] ?? 5;
        $numAlunos = $options['alunos'] ?? 6;

        $modelo = ModeloCartao::factory()->create([
            'nucleo_id' => $escola->nucleo_id,
            'quantidade_questoes' => $numQuestoes,
            'status' => ModeloCartaoStatus::APPROVED,
            'criado_por' => $author->id,
            'homologado_por' => $author->id,
            'homologado_at' => now(),
        ]);

        $prova = Prova::factory()->create([
            'nucleo_id' => $escola->nucleo_id,
            'escola_id' => null,
            'modelo_cartao_id' => $modelo->id,
            'quantidade_questoes' => $numQuestoes,
            'quantidade_alternativas' => count(self::ALTERNATIVES),
            'alternativas' => self::ALTERNATIVES,
            'status' => ProvaStatus::PUBLISHED,
            'criado_por' => $author->id,
            'publicada_at' => now(),
        ]);

        $gabarito = GabaritoOficial::factory()->create([
            'prova_id' => $prova->id,
            'versao' => 1,
            'status' => GabaritoOficialStatus::CURRENT,
            'criado_por' => $author->id,
            'publicado_por' => $author->id,
            'publicado_at' => now(),
        ]);

        $questoes = collect(range(1, $numQuestoes))->map(function (int $numero) use ($prova, $gabarito): Questao {
            $questao = Questao::factory()->create([
                'prova_id' => $prova->id,
                'numero' => $numero,
                'peso_padrao' => 1,
            ]);

            GabaritoResposta::create([
                'prova_id' => $prova->id,
                'gabarito_oficial_id' => $gabarito->id,
                'questao_id' => $questao->id,
                'alternativa_correta' => self::ALTERNATIVES[($numero - 1) % count(self::ALTERNATIVES)],
                'anulada' => false,
                'peso' => 1,
            ]);

            return $questao;
        });

        $turma = Turma::factory()->create(['escola_id' => $escola->id]);

        ProvaTurma::create([
            'prova_id' => $prova->id,
            'turma_id' => $turma->id,
            'data_prevista' => now()->toDateString(),
            'vinculado_por' => $author->id,
        ]);

        $matriculas = collect(range(1, $numAlunos))
            ->map(fn (): object => MatriculaTurma::factory()->create(['turma_id' => $turma->id]));

        return compact('modelo', 'prova', 'gabarito', 'questoes', 'turma', 'matriculas');
    }

    /**
     * Cria a aplicação (snapshot dos alunos ativos) AGENDADA e vincula aplicadores.
     *
     * @param  array{prova: Prova, gabarito: GabaritoOficial, turma: Turma}  $scenario
     * @param  list<User>  $aplicadores
     */
    public function createApplication(array $scenario, User $author, array $aplicadores = []): Aplicacao
    {
        $application = app(CreateAplicacaoAction::class)->execute(
            $scenario['prova'],
            $scenario['turma'],
            $scenario['gabarito'],
            ['titulo' => 'Aplicacao '.$scenario['prova']->titulo],
            $author,
        );

        foreach ($aplicadores as $aplicador) {
            AplicacaoAplicador::create([
                'aplicacao_id' => $application->id,
                'usuario_id' => $aplicador->id,
                'papel' => 'aplicador',
                'inicio_at' => now(),
                'fim_at' => null,
            ]);
        }

        return $application;
    }

    /**
     * Cria a aplicação e a inicia (EM ANDAMENTO), pronta para receber leituras.
     *
     * @param  array{prova: Prova, gabarito: GabaritoOficial, turma: Turma}  $scenario
     * @param  list<User>  $aplicadores
     */
    public function startedApplication(array $scenario, User $author, array $aplicadores = []): Aplicacao
    {
        return app(StartAplicacaoAction::class)->execute(
            $this->createApplication($scenario, $author, $aplicadores),
            $author,
        );
    }

    /**
     * Captura uma leitura para um aluno do snapshot, com respostas batendo o
     * gabarito (exceto a questão informada em `ambigua_numero`, que vira
     * pendência de revisão). Reusa a action preliminar de leitura.
     *
     * @param  array{ambigua_numero?: int|null, operacao_id?: string}  $options
     */
    public function captureReading(Aplicacao $application, AplicacaoAluno $aluno, User $actor, array $options = []): LeituraCartao
    {
        $ambiguaNumero = $options['ambigua_numero'] ?? null;
        $application->loadMissing(['prova.questoes', 'gabaritoOficial.respostas']);
        $corretas = $application->gabaritoOficial->respostas->keyBy('questao_id');

        $respostas = $application->prova->questoes
            ->sortBy('numero')
            ->map(function (Questao $questao) use ($corretas, $ambiguaNumero): array {
                if ($questao->numero === $ambiguaNumero) {
                    return [
                        'questao_id' => $questao->id,
                        'alternativa_detectada' => null,
                        'tipo_deteccao' => 'ambigua',
                        'confianca' => 0.4,
                    ];
                }

                return [
                    'questao_id' => $questao->id,
                    'alternativa_detectada' => $corretas[$questao->id]->alternativa_correta,
                    'tipo_deteccao' => 'marcada',
                    'confianca' => 0.95,
                ];
            })
            ->values()
            ->all();

        $arquivo = Arquivo::factory()->create([
            'proprietario_tipo' => 'leitura_cartao',
            'proprietario_id' => $application->id,
            'criado_por_id' => $actor->id,
        ]);

        return app(CreatePreliminaryReadingAction::class)->execute($application, [
            'operacao_id' => $options['operacao_id'] ?? 'op-'.Str::uuid(),
            'aplicacao_aluno_id' => $aluno->id,
            'arquivo_original_id' => $arquivo->id,
            'confianca_geral' => 0.9,
            'respostas' => $respostas,
        ], $actor);
    }
}
