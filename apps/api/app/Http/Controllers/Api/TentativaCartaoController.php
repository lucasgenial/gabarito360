<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Cartao;
use App\Models\CartaoEnvio;
use App\Models\Prova;
use App\Services\Omr\HistoricoCartao;
use App\Support\ContextoCorrecao;
use App\Support\Correcao;
use App\Support\Escopo;
use App\Support\Permissao;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class TentativaCartaoController extends Controller
{
    public function index(Request $r, int $id)
    {
        $p = Escopo::prova($r->user(), $id);
        abort_unless($p && $r->user()->perfil !== 'aplicador', 404);
        $d = $r->validate(['aluno_id' => 'nullable|integer|min:1', 'page' => 'nullable|integer|min:1']);
        $alunos = ContextoCorrecao::alunos($p, $r->user())->pluck('alunos.id');
        if (! empty($d['aluno_id'])) {
            abort_unless($alunos->contains((int) $d['aluno_id']), 404);
        }
        $q = DB::table('cartao_tentativas')->where('prova_id', $id)->where('espaco_id', $p->espaco_id)
            ->where(fn ($x) => $x->whereNull('aluno_id')->orWhereIn('aluno_id', $alunos));
        if (! empty($d['aluno_id'])) {
            $q->where('aluno_id', $d['aluno_id']);
        }
        $pagina = $q->orderByDesc('ocorrido_em')->paginate(25);
        $rows = collect($pagina->items());
        $ids = $rows->pluck('id');
        $eventos = DB::table('cartao_tentativa_eventos')->whereIn('tentativa_id', $ids)->orderByDesc('id')->get()->unique('tentativa_id')->keyBy('tentativa_id');
        $vigentes = Cartao::whereIn('tentativa_id', $ids)->pluck('id', 'tentativa_id');
        $nomes = DB::table('alunos')->whereIn('id', $rows->pluck('aluno_id'))->pluck('nome', 'id');
        $autores = DB::table('usuarios')->whereIn('id', $rows->pluck('autor_id'))->pluck('nome', 'id');

        return ApiResponse::success(['prova' => ['id' => $p->id, 'titulo' => $p->titulo], 'tentativas' => $rows->map(fn ($t) => [
            'id' => $t->id, 'numero' => $t->numero, 'aluno_nome' => $nomes[$t->aluno_id] ?? 'Aluno não identificado', 'autor_nome' => $autores[$t->autor_id] ?? 'Registro anterior',
            'ocorrido_em' => $t->ocorrido_em, 'motivo'=>$eventos[$t->id]->motivo??null,
            'nota_final'=>isset($eventos[$t->id])?(json_decode(Crypt::decryptString($eventos[$t->id]->dados),true)['nota']['nota_final']??null):null,
            'estado' => isset($vigentes[$t->id]) ? 'vigente' : ($eventos[$t->id]->acao ?? 'registrada')])->all(),
            'pagina' => $pagina->currentPage(), 'ultima_pagina' => $pagina->lastPage(), 'total' => $pagina->total()]);
    }

    public function show(Request $r, string $id, HistoricoCartao $h)
    {
        $t = DB::table('cartao_tentativas')->find($id);
        abort_unless($t, 404);
        $p = $h->exigirAcesso($r->user(), $t);
        $d = $h->dados($t);
        $eventos = DB::table('cartao_tentativa_eventos')->where('tentativa_id', $id)->orderBy('id')->get();
        $ultima = $eventos->last();
        $vigente = Cartao::where('tentativa_id', $id)->first();
        $proposta = $ultima?->acao === 'aguardando_confirmacao';
        $autores = DB::table('usuarios')->whereIn('id', $eventos->pluck('ator_id')->push($t->autor_id))->pluck('nome', 'id');

        return ApiResponse::success(['id' => $t->id, 'numero' => $t->numero, 'prova' => ['id' => $p->id, 'titulo' => $p->titulo],
            'aluno_nome' => DB::table('alunos')->where('id', $t->aluno_id)->value('nome') ?? 'Aluno não identificado', 'aluno_id' => $t->aluno_id,
            'autor_nome' => $autores[$t->autor_id] ?? 'Registro anterior', 'ocorrido_em' => $t->ocorrido_em, 'estado' => $vigente ? 'vigente' : ($ultima?->acao ?? 'registrada'),
            'tem_imagem' => (bool) $t->imagem_path, 'imagem_sha256' => $t->imagem_sha256, 'qualidade' => $d['cartao']['qualidade'] ?? null,
            'leituras' => $d['leituras_propostas'] ?? $d['respostas'] ?? [], 'nota' => $d['nota'] ?? null,
            'pode_aplicar' => $proposta && Permissao::pode($r->user(), 'correcao.alterar') && ! Correcao::bloqueio($r->user(), $p),
            'pode_descartar' => $proposta && Permissao::pode($r->user(), 'correcao.alterar') && ! Correcao::bloqueio($r->user(), $p),
            'eventos' => $eventos->map(function ($e) use ($autores) {
                $snap = json_decode(Crypt::decryptString($e->dados), true, 512, JSON_THROW_ON_ERROR);

                return [
                    'acao' => $e->acao, 'ator_nome' => $snap['ator_registrado']['nome'] ?? $autores[$e->ator_id] ?? 'Sistema/registro anterior', 'perfil' => $snap['ator_registrado']['perfil'] ?? null, 'ocorrido_em' => $e->ocorrido_em, 'motivo' => $e->motivo,
                    'nota' => $snap['nota'] ?? null, 'respostas' => $snap['respostas'] ?? [], 'revisoes' => $snap['revisoes'] ?? []];
            })->all()]);
    }

    public function imagem(Request $r, string $id, HistoricoCartao $h)
    {
        $t = DB::table('cartao_tentativas')->find($id);
        abort_unless($t, 404);
        $h->exigirAcesso($r->user(), $t);
        $d = Storage::disk('local');
        abort_unless($t->imagem_path && $d->exists($t->imagem_path), 404);
        $p = $d->path($t->imagem_path);
        abort_unless(hash_equals($t->imagem_sha256, hash_file('sha256', $p)), 409, 'Foto arquivada com integridade divergente.');
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($p);
        abort_unless(in_array($mime, ['image/jpeg', 'image/png', 'image/webp', 'image/gif'], true), 404);

        return response()->file($p, ['Content-Type' => $mime, 'Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }

    public function aplicar(Request $r, string $id, HistoricoCartao $h)
    {
        $d = $r->validate(['motivo' => 'required|string|min:10|max:500', 'confirmar' => 'required|accepted']);
        $c = $h->aplicar($id, $r->user(), $d['motivo']);

        return ApiResponse::success(['aplicada' => true, 'cartao_id' => $c->id, 'prova_id' => $c->prova_id, 'aluno_id' => $c->aluno_id, 'status' => $c->status]);
    }

    public function descartar(Request $r, string $id, HistoricoCartao $h)
    {
        $t = DB::table('cartao_tentativas')->find($id);
        abort_unless($t, 404);
        $p = $h->exigirAcesso($r->user(), $t);
        abort_unless(Permissao::pode($r->user(), 'correcao.alterar'), 403);
        DB::transaction(function () use ($t, $p, $r, $h) {
            $p = Prova::whereKey($p->id)->lockForUpdate()->firstOrFail();
            $h->exigirAcesso($r->user(), $t);
            abort_if(Correcao::bloqueio($r->user(), $p), 403);
            $acao = DB::table('cartao_tentativa_eventos')->where('tentativa_id', $t->id)->orderByDesc('id')->value('acao');
            if ($acao === 'descartada') {
                return;
            }
            abort_unless($acao === 'aguardando_confirmacao', 409, 'Esta tentativa já foi encerrada.');
            $h->evento($t, 'descartada', null, $r->user(), 'Nova tentativa descartada; correção vigente mantida.');
            foreach (CartaoEnvio::where('prova_id', $p->id)->where('status', 'aguardando_confirmacao')->get() as $e) {
                if (($e->resultado['tentativa_id'] ?? null) === $t->id) {
                    $e->update(['status' => 'recusado', 'mensagem' => 'Tentativa descartada. Correção vigente mantida.']);
                }
            }
        }, 3);

        return ApiResponse::success(['descartada' => true]);
    }
}
