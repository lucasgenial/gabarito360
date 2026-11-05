<?php

namespace App\Services\Omr;

use App\Models\Cartao;
use App\Models\CartaoEnvio;
use App\Models\Prova;
use App\Models\Usuario;
use App\Support\ContextoCorrecao;
use App\Support\Correcao;
use App\Support\Escopo;
use App\Support\Permissao;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/** Chamadores mantêm lock da prova. Registros selados não são atualizados nem apagados. */
class HistoricoCartao
{
    public function conteudo(Cartao $c): array
    {
        $attrs = $c->getAttributes();
        unset($attrs['tentativa_id'],$attrs['updated_at']);
        $attrs['qualidade'] = $c->qualidade;

        return ['cartao' => $attrs, 'respostas' => DB::table('cartao_respostas')->where('cartao_id', $c->id)->orderBy('numero_questao')->get()->toArray(),
            'nota' => DB::table('notas')->where('cartao_id', $c->id)->first(),
            'revisoes' => DB::table('ambiguidade_logs')->where('cartao_id', $c->id)->orderBy('id')->get()->toArray()];
    }

    public function fingerprint(Cartao $c): string
    {
        return hash('sha256', json_encode($this->conteudo($c), JSON_THROW_ON_ERROR));
    }

    public function garantir(Cartao $c, ?Usuario $ator = null, array $extra = [], ?Cartao $anterior = null): object
    {
        if ($c->tentativa_id) {
            $t = DB::table('cartao_tentativas')->find($c->tentativa_id);
            abort_unless($t && (int) $t->prova_id === (int) $c->prova_id && (int) $t->aluno_id === (int) $c->aluno_id && (int) $t->espaco_id === (int) $c->espaco_id, 409);
            $this->verificarFoto($t);

            return $t;
        }
        if ($t = DB::table('cartao_tentativas')->where('origem_cartao_id', $c->id)->first()) {
            $this->verificarFoto($t);

            return $t;
        }
        $path = null;
        $hash = null;
        if ($c->imagem_url) {
            $disk = Storage::disk('public');
            abort_unless($disk->exists($c->imagem_url), 409, 'A foto anterior está indisponível. Restaure o arquivo antes de alterar a correção.');
            $bytes = $disk->get($c->imagem_url);
            $hash = hash('sha256', $bytes);
            $path = 'cartoes-historico/e'.($c->espaco_id ?? 0).'/p'.$c->prova_id.'/'.$hash.'.bin';
            $priv = Storage::disk('local');
            if (! $priv->exists($path)) {
                abort_unless($priv->put($path, $bytes), 503, 'Não foi possível preservar a foto. A correção atual foi mantida.');
            }
            abort_unless(hash_equals($hash, hash_file('sha256', $priv->path($path))), 409, 'A integridade da foto arquivada precisa ser conferida.');
        }
        $numero = 1 + (int) DB::table('cartao_tentativas')->where('prova_id', $c->prova_id)->where('espaco_id', $c->espaco_id)->where('aluno_id', $c->aluno_id)->max('numero');
        $id = (string) Str::uuid();
        $dados = $this->conteudo($c) + $extra;
        $dados['ator_registrado'] = $ator ? ['id' => $ator->id, 'nome' => $ator->nome, 'perfil' => $ator->perfil, 'vinculo_id' => $ator->vinculoContextoId()] : null;
        DB::table('cartao_tentativas')->insert(['id' => $id, 'espaco_id' => $c->espaco_id, 'prova_id' => $c->prova_id, 'aluno_id' => $c->aluno_id, 'origem_cartao_id' => $c->id, 'numero' => $numero,
            'contexto_chave' => hash('sha256', ($c->espaco_id ?? 0).':'.$c->prova_id.':'.($c->aluno_id ?? 0)),
            'autor_id' => $ator?->id ?? $c->criado_por, 'vinculo_id' => $ator?->vinculoContextoId(), 'ocorrido_em' => now()->utc(),
            'imagem_path' => $path, 'imagem_sha256' => $hash, 'dados' => Crypt::encryptString(json_encode($dados, JSON_THROW_ON_ERROR)),
            'anterior_cartao_id' => $anterior?->id, 'anterior_fingerprint' => $anterior ? $this->fingerprint($anterior) : null]);
        $timestamps = $c->timestamps;
        $c->timestamps = false;
        try {
            $c->forceFill(['tentativa_id' => $id])->save();
        } finally {
            $c->timestamps = $timestamps;
        }

        return DB::table('cartao_tentativas')->find($id);
    }

    public function evento(object $t, string $acao, ?Cartao $c, ?Usuario $ator, ?string $motivo = null, array $extra = []): void
    {
        $ant = DB::table('cartao_tentativa_eventos')->where('tentativa_id', $t->id)->orderByDesc('id')->value('sha256');
        $payload = ($c ? $this->conteudo($c) : []) + $extra;
        $payload['ator_registrado'] = $ator ? ['id' => $ator->id, 'nome' => $ator->nome, 'perfil' => $ator->perfil, 'vinculo_id' => $ator->vinculoContextoId()] : null;
        $dados = Crypt::encryptString(json_encode($payload, JSON_THROW_ON_ERROR));
        $data = now()->utc()->format('Y-m-d H:i:s.u');
        $hash = hash('sha256', json_encode([$t->id, $acao, $c?->id, $ator?->id, $ator?->vinculoContextoId(), $data, $motivo, $dados, $ant], JSON_THROW_ON_ERROR));
        DB::table('cartao_tentativa_eventos')->insert(['tentativa_id' => $t->id, 'acao' => $acao, 'cartao_id' => $c?->id, 'ator_id' => $ator?->id, 'vinculo_id' => $ator?->vinculoContextoId(),
            'ocorrido_em' => $data, 'motivo' => $motivo, 'dados' => $dados, 'sha256' => $hash, 'anterior_sha256' => $ant]);
    }

    private function verificarFoto(object $t): void
    {
        if (! $t->imagem_path) {
            return;
        }
        $d = Storage::disk('local');
        abort_unless($d->exists($t->imagem_path) && hash_equals($t->imagem_sha256, hash_file('sha256', $d->path($t->imagem_path))), 409,
            'A foto preservada precisa ser conferida. A correção vigente foi mantida.');
    }

    public function registrarAtual(Cartao $c, ?Usuario $ator, string $acao = 'vigente', ?string $motivo = null): object
    {
        $t = $this->garantir($c, $ator);
        $this->evento($t, $acao, $c, $ator, $motivo);

        return $t;
    }

    public function propor(Cartao $novo, Cartao $atual, CartaoEnvio $envio, Usuario $ator, array $leituras): void
    {
        if (! $atual->tentativa_id) {
            $this->registrarAtual($atual, null, 'registro_anterior');
        }
        $t = $this->garantir($novo, $ator, ['leituras_propostas' => $leituras], $atual);
        $this->evento($t, 'aguardando_confirmacao', null, $ator);
        $envio->update(['cartao_id' => null, 'status' => 'aguardando_confirmacao', 'mensagem' => 'Este aluno já possui uma correção nesta prova. A correção atual foi mantida. Confira a nova tentativa antes de aplicar.',
            'resultado' => ['tentativa_id' => $t->id, 'tentativa_numero' => $t->numero, 'prova_id' => $novo->prova_id, 'aluno_id' => $novo->aluno_id, 'aluno_nome' => DB::table('alunos')->where('id', $novo->aluno_id)->value('nome')]]);
        $novo->delete();
        DB::afterCommit(function () use ($novo) {
            if ($novo->imagem_url) {
                Storage::disk('public')->delete($novo->imagem_url);
            }
        });
    }

    public function exigirAcesso(Usuario $u, object $t): Prova
    {
        $p = Escopo::prova($u, $t->prova_id);
        abort_unless($p && (int) $p->espaco_id === (int) $t->espaco_id && $u->perfil !== 'aplicador' && Permissao::pode($u, 'correcao.acompanhar'), 404);
        if ($t->aluno_id) {
            abort_unless(ContextoCorrecao::alunos($p, $u)->where('alunos.id', $t->aluno_id)->exists(), 404);
        }

        return $p;
    }

    public function dados(object $t): array
    {
        return json_decode(Crypt::decryptString($t->dados), true, 512, JSON_THROW_ON_ERROR);
    }

    public function aplicar(string $id, Usuario $u, string $motivo): Cartao
    {
        $t = DB::table('cartao_tentativas')->find($id);
        abort_unless($t, 404);
        $p = $this->exigirAcesso($u, $t);
        abort_unless(Permissao::pode($u, 'correcao.alterar'), 403);

        return DB::transaction(function () use ($id, $u, $p, $motivo) {
            $p = Prova::whereKey($p->id)->lockForUpdate()->firstOrFail();
            $t = DB::table('cartao_tentativas')->find($id);
            $this->exigirAcesso($u, $t);
            abort_if(Correcao::bloqueio($u, $p), 403);
            $ultimo = DB::table('cartao_tentativa_eventos')->where('tentativa_id', $id)->orderByDesc('id')->first();
            if ($ultimo?->acao === 'aplicada') {
                return Cartao::findOrFail($ultimo->cartao_id);
            }
            abort_unless($ultimo?->acao === 'aguardando_confirmacao', 409, 'Esta tentativa já foi encerrada. Atualize o histórico.');
            $atual = ContextoCorrecao::cartoes($u)->lockForUpdate()->find($t->anterior_cartao_id);
            abort_unless($atual && (int) $atual->prova_id === (int) $p->id && (int) $atual->aluno_id === (int) $t->aluno_id
                && hash_equals($t->anterior_fingerprint, $this->fingerprint($atual)), 409, 'A correção vigente mudou desde o envio desta foto. Confira a versão atual antes de preparar outra tentativa.');
            $priv = Storage::disk('local');
            abort_unless($t->imagem_path && $priv->exists($t->imagem_path)
                && hash_equals($t->imagem_sha256, hash_file('sha256', $priv->path($t->imagem_path))), 409, 'A foto da tentativa precisa ser conferida. A correção atual foi mantida.');
            $d = $this->dados($t);
            abort_unless(! empty($d['leituras_propostas']) && count($d['leituras_propostas']) === (int) $p->num_questoes, 409);
            $path = 'cartoes/espacos/'.($t->espaco_id ?? 0).'/'.$p->id.'/'.Str::uuid().'.jpg';
            abort_unless(Storage::disk('public')->put($path, $priv->get($t->imagem_path)), 503);
            try {
                $this->registrarAtual($atual, $u, 'substituida', $motivo);
                CartaoEnvio::where('cartao_id', $atual->id)->update(['cartao_id' => null, 'status' => 'substituido', 'resultado' => null, 'mensagem' => 'Outra tentativa foi confirmada. A leitura anterior foi preservada no histórico.']);
                $oldPath = $atual->imagem_url;
                $atual->delete();
                $novo = new Cartao(['prova_id' => $p->id, 'aluno_id' => $t->aluno_id, 'imagem_url' => $path, 'status' => 'pendente', 'lingua' => $d['cartao']['lingua'] ?? null]);
                $novo->forceFill(ContextoCorrecao::autoria($t->espaco_id, $u->id) + ['tentativa_id' => $id, 'qualidade' => $d['cartao']['qualidade'] ?? null])->save();
                $novo->setRelation('prova', $p);
                GravadorLeituras::gravar($novo, $d['leituras_propostas'], $u->id);
                $this->evento($t, 'aplicada', $novo, $u, $motivo);
                foreach (CartaoEnvio::where('status', 'aguardando_confirmacao')->where('prova_id', $p->id)->get() as $envio) {
                    if (($envio->resultado['tentativa_id'] ?? null) === $id) {
                        $envio->update(['cartao_id' => $novo->id]);
                        ResultadoEnvio::concluir($envio, $novo);
                    }
                }
                DB::afterCommit(function () use ($novo, $oldPath) {
                    if ($oldPath) {
                        Storage::disk('public')->delete($oldPath);
                    }app(ImagensCartao::class)->gerar($novo);
                });
                AtualizadorStatusProva::atualizar($p);

                return $novo;
            } catch (\Throwable $e) {
                Storage::disk('public')->delete($path);
                throw $e;
            }
        }, 3);
    }
}
