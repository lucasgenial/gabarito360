<?php

namespace App\Jobs;

use App\Models\Cartao;
use App\Models\Prova;
use App\Services\Omr\AtualizadorStatusProva;
use App\Services\Omr\CalculadoraNota;
use App\Services\Omr\ImagensCartao;
use App\Services\Omr\OmrDriverInterface;
use App\Services\Omr\OmrIdentificacaoInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProcessarCartaoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const LIMIAR_CONFIANCA = 0.90;

    public function __construct(public int $cartaoId) {}

    public function handle(OmrDriverInterface $driver, ImagensCartao $imagens): void
    {
        $cartao = Cartao::find($this->cartaoId);
        if (!$cartao) {
            return;
        }

        $prova = $cartao->prova;

        $caminho = Storage::disk('public')->path($cartao->imagem_url);
        $leituras = $driver->processar($caminho, $prova->num_questoes, $prova->num_alternativas);

        if ($driver instanceof OmrIdentificacaoInterface) {
            // Cartão de outra prova (ex.: fotografou o de Linguagens na tela de Ciências Humanas):
            // vai para a prova do QR, se for da mesma escola, e é relido com o gabarito dela.
            $provaQr = (int) ($driver->identificacao()['prova_id'] ?? 0);
            $outra   = $this->provaDoQr($cartao, $driver->identificacao());
            if ($provaQr && $provaQr !== (int) $prova->id && !$outra) {
                $this->recusar($cartao, $prova, $provaQr);
                return;
            }
            if ($outra) {
                $anterior = $prova;
                $cartao->update(['prova_id' => $outra->id]);
                $cartao->refresh();
                $prova    = $outra;
                $leituras = $driver->processar($caminho, $prova->num_questoes, $prova->num_alternativas);
                AtualizadorStatusProva::atualizar($anterior);
            }

            // Identificação pelo QR do cartão nativo (aluno e língua estrangeira)
            $this->aplicarIdentificacao($cartao, $driver->identificacao());
            $this->substituirLeiturasAnteriores($cartao);
        }

        DB::table('cartao_respostas')->where('cartao_id', $cartao->id)->delete();

        $rows               = [];
        $somaConfianca      = 0.0;
        $temAmbiguaPendente = false;

        foreach ($leituras as $l) {
            $ambigua = $l['ambigua'] || $l['confianca'] < self::LIMIAR_CONFIANCA;

            // Quando a prova está configurada para anular questões com marcação dupla,
            // a divergência é resolvida automaticamente como questão em branco, sem
            // exigir intervenção manual do professor/coordenador.
            if ($ambigua && $l['multiplas_marcacoes'] && $prova->anular_se_todas) {
                $ambigua = false;
                $l['alternativa'] = null;
            }

            if ($ambigua) {
                $temAmbiguaPendente = true;
            }

            $somaConfianca += $l['confianca'];

            $rows[] = [
                'cartao_id'               => $cartao->id,
                'numero_questao'          => $l['numero_questao'],
                'alternativa'             => $ambigua ? null : $l['alternativa'],
                'confianca'               => $l['confianca'],
                'ambigua'                 => $ambigua,
                'alternativas_detectadas' => json_encode($l['alternativas_detectadas']),
            ];
        }

        if ($rows) {
            DB::table('cartao_respostas')->insert($rows);
        }

        $confiancaGeral = count($leituras) ? $somaConfianca / count($leituras) : 0;
        $status         = $temAmbiguaPendente ? 'ambiguo' : 'lido';

        $cartao->update([
            'status'          => $status,
            'confianca_geral' => $confiancaGeral,
        ]);

        if ($status === 'lido') {
            CalculadoraNota::calcular($cartao);
        }

        $imagens->gerar($cartao);

        AtualizadorStatusProva::atualizar($prova);
    }

    /** Prova indicada pelo QR quando difere da prova do envio e pode receber o cartão. */
    private function provaDoQr(Cartao $cartao, array $id): ?Prova
    {
        $provaQr = (int) ($id['prova_id'] ?? 0);
        if (!$provaQr || $provaQr === (int) $cartao->prova_id) {
            return null;
        }

        $outra = Prova::find($provaQr);

        return ($outra
            && (int) $outra->escola_id === (int) $cartao->prova->escola_id
            && in_array($outra->status, ['publicada', 'em_correcao', 'corrigida'], true))
            ? $outra : null;
    }

    /**
     * QR de outra prova que não pode receber este cartão (outra escola ou prova não publicada):
     * não corrige com o gabarito errado — apaga o envio e deixa o motivo para a resposta do upload.
     */
    private function recusar(Cartao $cartao, Prova $prova, int $provaQr): void
    {
        $outra = Prova::find($provaQr);
        $motivo = ($outra && (int) $outra->escola_id === (int) $prova->escola_id)
            ? "Este cartão não é de {$prova->disciplina}: é da prova \"{$outra->disciplina}\" ({$outra->titulo}), "
              . 'que ainda não está publicada para correção. Publique o gabarito dela e fotografe de novo.'
            : "Este cartão não é de {$prova->disciplina}: o QR é de uma prova de outra escola ou que você não acessa. "
              . 'Confira se imprimiu o cartão na prova certa.';

        Cache::put(self::chaveRecusa($cartao->id), $motivo, now()->addMinutes(10));
        if ($cartao->imagem_url) {
            Storage::disk('public')->delete($cartao->imagem_url);
        }
        $cartao->delete();
        AtualizadorStatusProva::atualizar($prova);
    }

    public static function chaveRecusa(int $cartaoId): string
    {
        return "g360:cartao-recusado:{$cartaoId}";
    }

    /** Nova foto do mesmo aluno na mesma prova substitui a anterior (sem cartão duplicado). */
    private function substituirLeiturasAnteriores(Cartao $cartao): void
    {
        if (!$cartao->aluno_id) {
            return;
        }

        Cartao::where('prova_id', $cartao->prova_id)
            ->where('aluno_id', $cartao->aluno_id)
            ->where('id', '!=', $cartao->id)
            ->get()
            ->each(function (Cartao $antigo) {
                if ($antigo->imagem_url) {
                    Storage::disk('public')->delete($antigo->imagem_url);
                }
                $antigo->delete(); // respostas, nota e logs saem em cascata
            });
    }

    private function aplicarIdentificacao(Cartao $cartao, array $id): void
    {
        $dados = [];

        if (!empty($id['lingua']) && $cartao->prova->opcao_lingua) {
            $dados['lingua'] = $id['lingua'];
        }

        // Só vincula o aluno do QR se o cartão é desta prova e o aluno está numa turma dela.
        $alunoQr = (int) ($id['aluno_id'] ?? 0);
        if (!$cartao->aluno_id && $alunoQr && (int) ($id['prova_id'] ?? 0) === (int) $cartao->prova_id) {
            $pertence = DB::table('alunos')
                ->join('prova_turmas', 'prova_turmas.turma_id', '=', 'alunos.turma_id')
                ->where('prova_turmas.prova_id', $cartao->prova_id)
                ->where('alunos.id', $alunoQr)
                ->exists();
            if ($pertence) {
                $dados['aluno_id'] = $alunoQr;
            }
        }

        if ($dados) {
            $cartao->update($dados);
            $cartao->refresh();
        }
    }
}
