<?php

namespace App\Jobs;

use App\Models\Cartao;
use App\Models\CartaoEnvio;
use App\Models\Prova;
use App\Models\Usuario;
use App\Services\Omr\AtualizadorStatusProva;
use App\Services\Omr\CalculadoraNota;
use App\Services\Omr\ImagensCartao;
use App\Services\Omr\OmrDriverInterface;
use App\Services\Omr\OmrIdentificacaoInterface;
use App\Services\Omr\ResultadoEnvio;
use App\Support\Correcao;
use App\Support\Escopo;
use App\Support\Permissao;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ProcessarCartaoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout;

    public bool $failOnTimeout = true;

    public function __construct(public int $cartaoId, public int $envioId, public int $tentativa = 1)
    {
        // Duas leituras (redirecionamento pelo QR) e geração das versões leves.
        $this->timeout = 3 * max(1, (int) config('omr.timeout', 60)) + 30;
        $this->onQueue('alta');
    }

    public function backoff(): array
    {
        return [5, 20];
    }

    public function middleware(): array
    {
        return [(new WithoutOverlapping("cartao:{$this->cartaoId}"))->dontRelease()->expireAfter($this->timeout + 15)];
    }

    private function envioAtivo(): ?CartaoEnvio
    {
        return CartaoEnvio::whereKey($this->envioId)->where('tentativa', $this->tentativa)
            ->whereIn('status', ['na_fila', 'processando'])->lockForUpdate()->first();
    }

    public function handle(OmrDriverInterface $driver, ImagensCartao $imagens): void
    {
        $envio = $this->envioAtivo();
        if (! $envio) {
            return;
        } // Entrega repetida ou tentativa antiga não repete efeitos.
        $cartao = Cartao::find($this->cartaoId);
        if (! $cartao) {
            $envio->update(['status' => 'recusado', 'mensagem' => 'O cartão foi excluído. Fotografe novamente.']);

            return;
        }
        try {
            $usuario = Usuario::find($envio->usuario_id);
            Permissao::limparCache();
            $prova = Prova::find($envio->prova_id);
            if (! $this->podeLer($usuario, $prova)) {
                $this->recusar($envio, $cartao, 'A prova não está mais disponível para este envio.');

                return;
            }
            $envio->update(['status' => 'processando', 'mensagem' => null]);
            $caminho = Storage::disk('public')->path($cartao->imagem_url);
            $leituras = $driver->processar($caminho, $prova->num_questoes, $prova->num_alternativas);
            $id = $driver instanceof OmrIdentificacaoInterface ? $driver->identificacao() : [];
            $provaQr = (int) ($id['prova_id'] ?? 0);
            if ($provaQr && $provaQr !== (int) $prova->id) {
                $destino = Prova::find($provaQr);
                if (! $this->podeLer($usuario, $destino) || (int) $destino->escola_id !== (int) $prova->escola_id) {
                    $this->recusar($envio, $cartao, 'O QR indica outra prova que não está disponível para correção neste acesso. Confira o cartão e a prova.');

                    return;
                }
                $prova = $destino;
                $leituras = $driver->processar($caminho, $prova->num_questoes, $prova->num_alternativas);
                $id = $driver->identificacao();
            }
            // Trabalho caro fora da transação: não bloqueia os novos uploads na prova.
            $cartao->prova_id = $prova->id;
            $cartao->setRelation('prova', $prova);
            $imagens->gerar($cartao);
            DB::transaction(function () use ($envio, $cartao, $prova, $usuario, $leituras, $id) {
                // Ordem fixa evita deadlock em redirecionamentos cruzados.
                $provas = Prova::whereIn('id', [$envio->prova_id, $prova->id])->orderBy('id')->lockForUpdate()->get()->keyBy('id');
                $atual = Cartao::whereKey($cartao->id)->lockForUpdate()->first();
                $recibo = $this->envioAtivo();
                if (! $recibo) {
                    return;
                }
                if (! $atual) {
                    $recibo->update(['status' => 'recusado', 'mensagem' => 'O cartão foi excluído. Fotografe novamente.']);
                    DB::afterCommit(fn () => app(ImagensCartao::class)->apagar($cartao->id));

                    return;
                }
                Permissao::limparCache();
                $usuario = $usuario->fresh();
                if (! $this->podeLer($usuario, $provas->get($envio->prova_id)) || ! $this->podeLer($usuario, $provas->get($prova->id))) {
                    $this->recusar($recibo, $atual, 'A prova foi fechada ou o acesso mudou durante a leitura.');

                    return;
                }
                if ((int) $atual->prova_id !== (int) $prova->id) {
                    $atual->aluno_id = null;
                }
                $atual->prova_id = $prova->id;
                $atual->setRelation('prova', $provas->get($prova->id));
                $this->identificar($atual, $id);
                if ($atual->aluno_id) {
                    $anteriores = Cartao::where('prova_id', $prova->id)->where('aluno_id', $atual->aluno_id)
                        ->where('id', '!=', $atual->id)->whereIn('status', ['lido', 'ambiguo'])->get();
                    if ($anteriores->contains(fn ($c) => $c->id > $atual->id)) {
                        $this->substituido($recibo, $atual);
                        foreach ($provas as $p) {
                            AtualizadorStatusProva::atualizar($p);
                        }

                        return;
                    }
                    foreach ($anteriores as $antigo) {
                        CartaoEnvio::where('cartao_id', $antigo->id)->update(['status' => 'substituido', 'resultado' => null,
                            'mensagem' => 'Uma foto mais recente deste aluno substituiu esta leitura.']);
                        $this->apagar($antigo);
                    }
                }
                $this->gravarLeituras($atual, $leituras);
                foreach ($provas as $p) {
                    AtualizadorStatusProva::atualizar($p);
                }
                ResultadoEnvio::concluir($recibo, $atual);
            }, 3);
        } catch (Throwable $e) {
            CartaoEnvio::whereKey($this->envioId)->where('tentativa', $this->tentativa)->whereIn('status', ['na_fila', 'processando'])
                ->update(['status' => 'na_fila', 'mensagem' => 'A leitura será tentada novamente.']);
            throw $e;
        }
    }

    public function failed(?Throwable $e): void
    {
        CartaoEnvio::whereKey($this->envioId)->where('tentativa', $this->tentativa)->whereIn('status', ['na_fila', 'processando'])
            ->update(['status' => 'erro', 'mensagem' => 'Não foi possível ler esta foto. Tente novamente ou fotografe outro cartão.']);
    }

    private function podeLer(?Usuario $u, ?Prova $p): bool
    {
        return $u && $u->ativo && $p && in_array($p->status, ['publicada', 'em_correcao', 'corrigida'], true)
            && Permissao::pode($u, 'cartao.enviar') && Escopo::prova($u, $p->id) && ! Correcao::bloqueio($u, $p);
    }

    private function recusar(CartaoEnvio $envio, Cartao $cartao, string $mensagem): void
    {
        DB::transaction(function () use ($envio, $cartao, $mensagem) {
            $prova = Prova::whereKey($envio->prova_id)->lockForUpdate()->first();
            $envio = $this->envioAtivo();
            if (! $envio) {
                return;
            }
            $envio->update(['status' => 'recusado', 'mensagem' => $mensagem, 'resultado' => null]);
            $this->apagar($cartao);
            if ($prova) {
                AtualizadorStatusProva::atualizar($prova);
            }
        });
    }

    private function substituido(CartaoEnvio $envio, Cartao $cartao): void
    {
        $envio->update(['status' => 'substituido', 'resultado' => null,
            'mensagem' => 'Uma foto mais recente deste aluno substituiu esta leitura.']);
        $this->apagar($cartao);
    }

    private function apagar(Cartao $cartao): void
    {
        // Rollback preserva as fotos da leitura anterior.
        DB::table('cartoes')->where('id', $cartao->id)->delete();
        DB::afterCommit(function () use ($cartao) {
            if ($cartao->imagem_url) {
                Storage::disk('public')->delete($cartao->imagem_url);
            }
            app(ImagensCartao::class)->apagar($cartao->id);
        });
    }

    private function identificar(Cartao $cartao, array $id): void
    {
        if (in_array($id['lingua'] ?? null, ['ING', 'ESP'], true) && $cartao->prova->opcao_lingua) {
            $cartao->lingua = $id['lingua'];
        }
        $alunoQr = (int) ($id['aluno_id'] ?? 0);
        if (! $cartao->aluno_id && $alunoQr && (int) ($id['prova_id'] ?? 0) === (int) $cartao->prova_id
            && DB::table('alunos')->join('prova_turmas', 'prova_turmas.turma_id', '=', 'alunos.turma_id')
                ->where('prova_turmas.prova_id', $cartao->prova_id)->where('alunos.id', $alunoQr)->exists()) {
            $cartao->aluno_id = $alunoQr;
        }
    }

    private function gravarLeituras(Cartao $cartao, array $leituras): void
    {
        DB::table('cartao_respostas')->where('cartao_id', $cartao->id)->delete();
        $rows = [];
        $soma = 0;
        $pendente = false;
        foreach ($leituras as $l) {
            $ambigua = $l['ambigua'] || $l['confianca'] < 0.90;
            if ($ambigua && $l['multiplas_marcacoes'] && $cartao->prova->anular_se_todas) {
                $ambigua = false;
                $l['alternativa'] = null;
            }
            $pendente = $pendente || $ambigua;
            $soma += $l['confianca'];
            $rows[] = ['cartao_id' => $cartao->id, 'numero_questao' => $l['numero_questao'],
                'alternativa' => $ambigua ? null : $l['alternativa'], 'confianca' => $l['confianca'],
                'ambigua' => $ambigua, 'alternativas_detectadas' => json_encode($l['alternativas_detectadas'])];
        }
        if ($rows) {
            DB::table('cartao_respostas')->insert($rows);
        }
        $cartao->status = $pendente ? 'ambiguo' : 'lido';
        $cartao->confianca_geral = count($leituras) ? $soma / count($leituras) : 0;
        $cartao->save();
        if (! $pendente) {
            CalculadoraNota::calcular($cartao);
        }
    }
}
