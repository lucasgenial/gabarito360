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
use App\Support\Espacos;
use App\Support\ContextoCorrecao;
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

    public ?int $vinculoId = null;

    public function __construct(public int $cartaoId, public int $envioId, public int $tentativa = 1, public ?int $espacoId = null, public ?int $atorId = null, ?int $vinculoId = null)
    {
        $this->vinculoId = $vinculoId;
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
        return [(new WithoutOverlapping("cartao:{$this->espacoId}:{$this->cartaoId}"))->dontRelease()->expireAfter($this->timeout + 15)];
    }

    private function envioAtivo(): ?CartaoEnvio
    {
        return CartaoEnvio::whereKey($this->envioId)->where('tentativa', $this->tentativa)
            ->where('vinculo_id', $this->vinculoId)
            ->when(Espacos::ativo(), fn ($q) => $q->where('espaco_id', $this->espacoId ?: 0)->where('usuario_id', $this->atorId ?: 0)->where('cartao_id', $this->cartaoId))
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
        if (Espacos::ativo() && (!ContextoCorrecao::cartaoValido($cartao) || (int) $cartao->espaco_id !== (int) $this->espacoId)) {
            $envio->update(['status' => 'recusado', 'resultado' => null, 'mensagem' => 'Contexto do cartão indisponível.']);
            return;
        }
        try {
            $usuario = Usuario::find($envio->usuario_id);
            if ($usuario && $this->vinculoId !== null) $usuario = $usuario->noVinculo($this->vinculoId);
            Permissao::limparCache();
            $prova = Prova::find($envio->prova_id);
            if (! $this->podeLer($usuario, $prova) || (Espacos::ativo() && $cartao->aluno_id && !ContextoCorrecao::alunos($prova, $usuario)->where('alunos.id', $cartao->aluno_id)->exists())) {
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
                    DB::afterCommit(fn () => app(ImagensCartao::class)->apagar($cartao->id, $cartao->espaco_id));

                    return;
                }
                Permissao::limparCache();
                $usuario = $usuario->fresh();
                if ((Espacos::ativo() && (!ContextoCorrecao::cartaoValido($atual) || (int) $atual->espaco_id !== (int) $this->espacoId)) || ! $this->podeLer($usuario, $provas->get($envio->prova_id)) || ! $this->podeLer($usuario, $provas->get($prova->id))) {
                    $this->recusar($recibo, $atual, 'A prova foi fechada ou o acesso mudou durante a leitura.');

                    return;
                }
                if ((int) $atual->prova_id !== (int) $prova->id) {
                    $atual->aluno_id = null;
                }
                $atual->prova_id = $prova->id;
                $atual->setRelation('prova', $provas->get($prova->id));
                $this->identificar($atual, $id, $usuario);
                if (Espacos::ativo() && $atual->aluno_id && !ContextoCorrecao::alunos($atual->prova, $usuario)->where('alunos.id', $atual->aluno_id)->exists()) {
                    $this->recusar($recibo, $atual, 'O acesso ao aluno mudou durante a leitura.');
                    return;
                }
                if ($atual->aluno_id) {
                    $anteriores = ContextoCorrecao::filtrar(Cartao::query(), $prova->espaco_id)->where('prova_id', $prova->id)->where('aluno_id', $atual->aluno_id)
                        ->where('id', '!=', $atual->id)->where(function ($q) use ($atual) {
                            $q->whereIn('status', ['lido', 'ambiguo'])
                                ->orWhere(function ($falhou) use ($atual) {
                                    $falhou->where('status', 'pendente')->where('id', '<', $atual->id)
                                        ->whereIn('id', CartaoEnvio::where('status', 'erro')->select('cartao_id'));
                                });
                        })->get();
                    if ($anteriores->contains(fn ($c) => $c->id > $atual->id)) {
                        $this->substituido($recibo, $atual);
                        foreach ($provas as $p) {
                            AtualizadorStatusProva::atualizar($p);
                        }

                        return;
                    }
                    foreach ($anteriores as $antigo) {
                        ContextoCorrecao::filtrar(CartaoEnvio::query(), $this->espacoId)->where('cartao_id', $antigo->id)->update(['status' => 'substituido', 'resultado' => null,
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
            if (($this->vinculoId !== null || \App\Support\RegimeVinculos::obrigatorio()) && $e instanceof \Symfony\Component\HttpKernel\Exception\HttpException
                && in_array($e->getStatusCode(), [403, 404], true)) {
                $this->recusar($envio, $cartao, 'O vínculo não está mais disponível para este envio.');
                return;
            }
            CartaoEnvio::whereKey($this->envioId)->when(Espacos::ativo(), fn ($q) => $q->where('espaco_id', $this->espacoId ?: 0)->where('usuario_id', $this->atorId ?: 0)->where('cartao_id', $this->cartaoId))->where('tentativa', $this->tentativa)->whereIn('status', ['na_fila', 'processando'])
                ->where('vinculo_id', $this->vinculoId)
                ->update(['status' => 'na_fila', 'mensagem' => 'A leitura será tentada novamente.']);
            throw $e;
        }
    }

    public function failed(?Throwable $e): void
    {
        CartaoEnvio::whereKey($this->envioId)->when(Espacos::ativo(), fn ($q) => $q->where('espaco_id', $this->espacoId ?: 0)->where('usuario_id', $this->atorId ?: 0)->where('cartao_id', $this->cartaoId))->where('tentativa', $this->tentativa)->whereIn('status', ['na_fila', 'processando'])
            ->where('vinculo_id', $this->vinculoId)
            ->update(['status' => 'erro', 'mensagem' => 'Não foi possível ler esta foto. Tente novamente ou fotografe outro cartão.']);
    }

    private function podeLer(?Usuario $u, ?Prova $p): bool
    {
        return $u && $u->ativo && $p && (!Espacos::ativo() || ((int) $p->espaco_id === (int) $this->espacoId && ContextoCorrecao::provaValida($p))) && in_array($p->status, ['publicada', 'em_correcao', 'corrigida'], true)
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
            if (Espacos::ativo() || $this->vinculoId !== null || \App\Support\RegimeVinculos::obrigatorio()) return; // Recusa contextual nunca usa a exclusão do fluxo legado.
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
        ContextoCorrecao::filtrar(CartaoEnvio::query(), $cartao->espaco_id)->where('cartao_id', $cartao->id)->update(['cartao_id' => null]);
        DB::table('cartoes')->where('id', $cartao->id)->delete();
        DB::afterCommit(function () use ($cartao) {
            if ($cartao->imagem_url) {
                Storage::disk('public')->delete($cartao->imagem_url);
            }
            app(ImagensCartao::class)->apagar($cartao->id, $cartao->espaco_id);
        });
    }

    private function identificar(Cartao $cartao, array $id, Usuario $usuario): void
    {
        if (in_array($id['lingua'] ?? null, ['ING', 'ESP'], true) && $cartao->prova->opcao_lingua) {
            $cartao->lingua = $id['lingua'];
        }
        $alunoQr = (int) ($id['aluno_id'] ?? 0);
        if (! $cartao->aluno_id && $alunoQr && (int) ($id['prova_id'] ?? 0) === (int) $cartao->prova_id
            && ContextoCorrecao::alunos($cartao->prova, $usuario)->where('alunos.id', $alunoQr)->exists()) {
            $cartao->aluno_id = $alunoQr;
        }
    }

    private function gravarLeituras(Cartao $cartao, array $leituras): void
    {
        ContextoCorrecao::exigirFilhos('cartao_respostas', 'cartao_id', $cartao->id, $cartao->espaco_id);
        ContextoCorrecao::filtrar(DB::table('cartao_respostas'), $cartao->espaco_id)->where('cartao_id', $cartao->id)->delete();
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
                'ambigua' => $ambigua, 'alternativas_detectadas' => json_encode($l['alternativas_detectadas'])] + ContextoCorrecao::autoria($cartao->espaco_id, $this->atorId);
        }
        if ($rows) {
            DB::table('cartao_respostas')->insert($rows);
        }
        $cartao->status = $pendente ? 'ambiguo' : 'lido';
        $cartao->confianca_geral = count($leituras) ? $soma / count($leituras) : 0;
        $cartao->save();
        if (! $pendente) {
            CalculadoraNota::calcular($cartao, $this->atorId);
        }
    }
}
