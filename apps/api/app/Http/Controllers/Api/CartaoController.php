<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Cartao;
use App\Models\CartaoEnvio;
use App\Models\Prova;
use App\Services\Omr\AtualizadorStatusProva;
use App\Services\Omr\CalculadoraNota;
use App\Services\Omr\ImagensCartao;
use App\Services\Omr\GeradorCartoes;
use App\Services\Omr\FilaCartoes;
use App\Services\Omr\ResultadoEnvio;
use App\Support\Correcao;
use App\Support\Escopo;
use App\Support\Espacos;
use App\Support\ContextoCorrecao;
use App\Support\Permissao;
use App\Models\Usuario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CartaoController extends Controller
{
    /**
     * Prova dentro do escopo do usuário. O aplicador só pode ENVIAR cartões
     * (doc 03, †APLIC): nas demais ações a prova não é encontrada para ele.
     */
    /** Decisões da revisão além das letras, com o código gravado em ambiguidade_logs. */
    private const DECISOES = ['BRANCO' => '-', 'ANULADA' => 'N'];

    private function encontrarProva(Request $request, int $provaId, bool $envio = false): ?Prova
    {
        if ($request->user()->perfil === 'aplicador' && !$envio) {
            return null;
        }

        return Escopo::prova($request->user(), $provaId);
    }

    public function index(Request $request, int $provaId, ImagensCartao $imagens): JsonResponse
    {
        $prova = $this->encontrarProva($request, $provaId);
        if (!$prova) {
            return ApiResponse::notFound('Prova não encontrada.');
        }

        // Lista para a tela de correção: aluno, turma, nota e se há foto (a foto vem por /cartoes/{id}/imagem)
        $cartoes = DB::table('cartoes')->when(Espacos::ativo(), fn ($q) => $q->whereIn('cartoes.id', ContextoCorrecao::cartoes($request->user())->select('cartoes.id')))
            ->leftJoin('cartao_tentativas as tentativa','tentativa.id','=','cartoes.tentativa_id')
            ->leftJoin('alunos', 'alunos.id', '=', 'cartoes.aluno_id')
            ->leftJoin('turmas', fn ($j) => $j->whereRaw('turmas.id = COALESCE(cartoes.turma_aplicacao_id, alunos.turma_id)'))
            ->leftJoin('notas', function ($j) {
                $j->on('notas.cartao_id', '=', 'cartoes.id');
                if (Espacos::ativo()) $j->on('notas.espaco_id', '=', 'cartoes.espaco_id')->on('notas.aluno_id', '=', 'cartoes.aluno_id')->on('notas.prova_id', '=', 'cartoes.prova_id');
            })
            ->where('cartoes.prova_id', $provaId)
            ->orderBy('alunos.nome')
            ->orderByDesc('cartoes.id')
            ->get([
                'cartoes.id', 'cartoes.espaco_id', 'cartoes.status', 'cartoes.aluno_id', 'cartoes.lingua', 'cartoes.created_at',
                'cartoes.imagem_url', 'alunos.nome as aluno_nome', 'alunos.matricula', 'turmas.nome as turma',
                'tentativa.numero as tentativa_numero',
                'notas.acertos', 'notas.total_questoes', 'notas.nota_final',
            ]);
        $urls=$imagens->urlsDaProva($prova,$cartoes->pluck('id')->all(),['original','miniatura'],$request->user());
        $ambiguas=ContextoCorrecao::filtrar(DB::table('cartao_respostas'),$prova->espaco_id)
            ->whereIn('cartao_id',$cartoes->pluck('id'))->where('ambigua',true)->groupBy('cartao_id')->selectRaw('cartao_id, COUNT(*) as total')->pluck('total','cartao_id');
        $cartoes=$cartoes->map(function ($c) use ($urls,$ambiguas) {
                $c->tem_imagem = (bool) $c->imagem_url;
                $c->foto_url = $c->imagem_url ? $urls[$c->id]['original'] : null;
                $c->miniatura_url = Storage::disk('public')->exists(ImagensCartao::disponivel($c->id, 'miniatura', $c->espaco_id))
                    ? ($urls[$c->id]['miniatura']??null)
                    : null;
                unset($c->imagem_url);
                $c->ambiguas = (int)($ambiguas[$c->id]??0);
                return $c;
            });

        return ApiResponse::success(['cartoes' => $cartoes]);
    }

    /** Foto do cartão (dado de aluno: só para quem acessa a prova; nunca por URL pública). */
    public function imagem(Request $request, int $id)
    {
        return $this->arquivo($request, $id, 'original');
    }

    /** Arquivo autenticado: a identidade vem do Bearer, nunca de parâmetros da URL. */
    public function arquivo(Request $request, int $id, string $tipo)
    {
        abort_unless(in_array($tipo, ImagensCartao::TIPOS, true), 404);
        $request->validate(array_fill_keys(['ator','vinculo','espaco','acesso','signature','expires','token','_ctx'], 'prohibited'));
        $ator = $request->user();
        abort_unless($ator && $ator->ativo && $ator->perfil !== 'aplicador' && Permissao::pode($ator, 'correcao.acompanhar'), 404);
        $cartao = ContextoCorrecao::cartoes($ator)->find($id);
        abort_unless(ContextoCorrecao::cartaoValido($cartao) && $this->encontrarProva($request, $cartao->prova_id), 404);
        $relativo = $tipo === 'original' ? $cartao?->imagem_url : ImagensCartao::disponivel($id, $tipo, $cartao?->espaco_id);
        $caminho = $cartao && $relativo ? Storage::disk('public')->path($relativo) : null;
        if (!$caminho || !is_file($caminho)) return ApiResponse::notFound('Imagem não encontrada.');
        return response()->file($caminho, ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }

    /** Exclui a correção (cartão, respostas, nota e foto) para o aluno ser fotografado de novo. */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $cartao = ContextoCorrecao::cartoes($request->user())->find($id);
        $prova  = $cartao ? $this->encontrarProva($request, $cartao->prova_id) : null;
        if (!$cartao || !$prova) {
            return ApiResponse::notFound('Cartão não encontrado.');
        }
        if ($bloqueio = Correcao::bloqueio($request->user(), $prova)) {
            return ApiResponse::error($bloqueio, ['motivo' => 'correcao_fechada'], 403);
        }

        $data=$request->validate(['motivo'=>'required|string|min:10|max:500','versao'=>'required|string|size:64']);
        DB::transaction(function () use ($cartao, $prova, $request, $data) {
            $provaAtual = Prova::whereKey($prova->id)->lockForUpdate()->firstOrFail();
            $atual = ContextoCorrecao::cartoes($request->user())->whereKey($cartao->id)->lockForUpdate()->first();
            if (! $atual) {
                return;
            }
            abort_if((int) $atual->prova_id !== (int) $provaAtual->id, 409,
                'O cartão mudou de prova durante a leitura. Atualize a página antes de excluir.');
            abort_unless(ContextoCorrecao::cartaoValido($atual, $provaAtual), 409);
            abort_unless(hash_equals($data['versao'],app(\App\Services\Omr\HistoricoCartao::class)->fingerprint($atual)),409,
                'A correção mudou durante a conferência. Abra novamente a confirmação para ver a versão atual.');
            ContextoCorrecao::filtrar(CartaoEnvio::query(), $atual->espaco_id)->where('cartao_id', $atual->id)->update(['status' => 'recusado', 'resultado' => null, 'cartao_id' => null,
                'mensagem' => 'O cartão foi excluído. Fotografe novamente.']);
            app(\App\Services\Omr\HistoricoCartao::class)->registrarAtual($atual,$request->user(),'excluida',$data['motivo']);
            $atual->delete(); // snapshot e foto privados permanecem consultáveis após a cascata operacional
            AtualizadorStatusProva::atualizar($provaAtual);
            DB::afterCommit(function () use ($atual) {
                if ($atual->imagem_url) {
                    Storage::disk('public')->delete($atual->imagem_url);
                }
                app(ImagensCartao::class)->apagar($atual->id, $atual->espaco_id);
            });
        });

        return ApiResponse::success(['excluido' => true]);
    }

    public function status(Request $request, int $provaId): JsonResponse
    {
        $prova = $this->encontrarProva($request, $provaId);
        if (!$prova) {
            return ApiResponse::notFound('Prova não encontrada.');
        }

        $base = DB::table('cartoes')->when(Espacos::ativo(), fn ($q) => $q->whereIn('cartoes.id', ContextoCorrecao::cartoes($request->user())->select('cartoes.id')))->where('prova_id', $provaId);

        $total     = (clone $base)->count();
        $lidos     = (clone $base)->where('status', 'lido')->count();
        $ambiguos  = (clone $base)->where('status', 'ambiguo')->count();
        $pendentes = $total - $lidos - $ambiguos;

        $ambiguosLista = DB::table('cartoes')->when(Espacos::ativo(), fn ($q) => $q->whereIn('cartoes.id', ContextoCorrecao::cartoes($request->user())->select('cartoes.id')))
            ->join('cartao_respostas', 'cartao_respostas.cartao_id', '=', 'cartoes.id')
            ->when(Espacos::ativo(), fn ($q) => $q->whereColumn('cartao_respostas.espaco_id', 'cartoes.espaco_id'))
            ->where('cartoes.prova_id', $provaId)
            ->where('cartoes.status', 'ambiguo')
            ->where('cartao_respostas.ambigua', true)
            ->select(
                'cartoes.id as cartao_id',
                'cartao_respostas.id as resposta_id',
                'cartao_respostas.numero_questao',
                'cartao_respostas.alternativas_detectadas'
            )
            ->get()
            ->map(function ($r) {
                $r->alternativas_detectadas = json_decode($r->alternativas_detectadas, true) ?? [];
                return $r;
            });

        $recentes = DB::table('cartoes')->when(Espacos::ativo(), fn ($q) => $q->whereIn('cartoes.id', ContextoCorrecao::cartoes($request->user())->select('cartoes.id')))
            ->join('notas', function ($j) {
                $j->on('notas.cartao_id', '=', 'cartoes.id');
                if (Espacos::ativo()) $j->on('notas.espaco_id', '=', 'cartoes.espaco_id')->on('notas.aluno_id', '=', 'cartoes.aluno_id')->on('notas.prova_id', '=', 'cartoes.prova_id');
            })
            ->leftJoin('alunos', 'alunos.id', '=', 'cartoes.aluno_id')
            ->where('cartoes.prova_id', $provaId)
            ->orderByDesc('notas.updated_at')
            ->limit(8)
            ->select('cartoes.id as cartao_id', 'alunos.nome as aluno_nome', 'notas.nota_final', 'notas.updated_at')
            ->get();

        return ApiResponse::success([
            'total'        => $total,
            'lidos'        => $lidos,
            'pendentes'    => $pendentes,
            'ambiguos'     => $ambiguos,
            ...\App\Services\Omr\ProgressoCorrecao::calcular($prova,$request->user()),
            'ambiguidades' => $ambiguosLista,
            'recentes'     => $recentes,
            'prova_status' => $prova->status,
            'correcao'     => Correcao::situacao($prova->fresh()),
        ]);
    }

    public function store(Request $request, int $provaId, FilaCartoes $fila): JsonResponse
    {
        $prova = $this->encontrarProva($request, $provaId, envio: true);
        if (! $prova) {
            return ApiResponse::notFound('Prova não encontrada.');
        }
        if ($bloqueio = Correcao::bloqueio($request->user(), $prova)) {
            return ApiResponse::error($bloqueio, ['motivo' => 'correcao_fechada'], 403);
        }

        if (! in_array($prova->status, ['publicada', 'em_correcao', 'corrigida'], true)) {
            return ApiResponse::error('A prova precisa estar publicada para receber cartões.', [], 422);
        }

        $data = $request->validate([
            'imagem' => 'required|image|max:10240',
            'aluno_id' => 'sometimes|integer|exists:alunos,id|nullable',
            'envio_id' => 'sometimes|uuid',
        ]);

        if (! empty($data['aluno_id']) && ! ContextoCorrecao::alunos($prova, $request->user())->where('alunos.id', $data['aluno_id'])->exists()) {
            return ApiResponse::error('O aluno não pertence às turmas desta prova.');
        }
        $envio = $fila->receber($request->user(), $provaId, $data['envio_id'] ?? (string) Str::uuid(),
            $request->file('imagem'), $data['aluno_id'] ?? null);

        return ApiResponse::success(ResultadoEnvio::resposta($envio, $request->user()), null, 202);
    }

    /** Só o autor acompanha o envio, sempre dentro do escopo original da prova. */
    public function envio(Request $request, int $provaId, string $chave): JsonResponse
    {
        if (! ($prova = $this->encontrarProva($request, $provaId, envio: true))) {
            return ApiResponse::notFound('Envio não encontrado.');
        }
        $envio = ContextoCorrecao::filtrar(CartaoEnvio::query(), $prova?->espaco_id)->where('usuario_id', $request->user()->id)
            ->where('vinculo_id', $request->user()->vinculoContextoId())
            ->where('prova_id', $provaId)->where('chave', CartaoEnvio::chaveContextual($request->user(), $chave, $prova?->espaco_id))->first();
        if (! $envio) {
            return ApiResponse::notFound('Envio não encontrado.');
        }
        $destino = $envio->resultado['prova_id'] ?? $provaId;
        if (! Escopo::prova($request->user(), $destino)) {
            return ApiResponse::notFound('Envio não encontrado.');
        }

        return ApiResponse::success(ResultadoEnvio::resposta($envio, $request->user()));
    }

    public function repetirEnvio(Request $request, int $provaId, string $chave, FilaCartoes $fila): JsonResponse
    {
        $prova = $this->encontrarProva($request, $provaId, envio: true);
        $envio = ContextoCorrecao::filtrar(CartaoEnvio::query(), $prova?->espaco_id)->where('usuario_id', $request->user()->id)
            ->where('vinculo_id', $request->user()->vinculoContextoId())
            ->where('prova_id', $provaId)->where('chave', CartaoEnvio::chaveContextual($request->user(), $chave, $prova?->espaco_id))->first();
        if (! $prova || ! $envio) {
            return ApiResponse::notFound('Envio não encontrado.');
        }
        if ($bloqueio = Correcao::bloqueio($request->user(), $prova)) {
            return ApiResponse::forbidden($bloqueio);
        }
        if (! in_array($prova->status, ['publicada', 'em_correcao', 'corrigida'], true)) {
            return ApiResponse::error('A prova precisa estar publicada para receber cartões.');
        }

        return ApiResponse::success(ResultadoEnvio::resposta($fila->repetir($envio, $request->user()), $request->user()), null, 202);
    }

    /**
     * PDF dos cartões-resposta nativos (um por aluno + brancos), com QR de identificação.
     * Exceção documentada à regra "API só JSON": é download de arquivo, não tela.
     */
    public function pdf(Request $request, int $provaId, GeradorCartoes $gerador)
    {
        $prova = $this->encontrarProva($request, $provaId);
        if (!$prova) {
            return ApiResponse::notFound('Prova não encontrada.');
        }
        \App\Services\DuplicarProva::conferirAplicacao($request->user(),$prova,true);

        $data = $request->validate([
            'turma_id' => 'sometimes|integer|nullable',
            'brancos'  => 'sometimes|integer|min:0|max:200',
        ]);

        if (Espacos::ativo() && !empty($data['turma_id'])) {
            abort_unless(in_array((int) $data['turma_id'], Escopo::turmaIds($request->user()) ?? [], true) && ContextoCorrecao::filtrar(DB::table('prova_turmas'), $prova->espaco_id)->where('prova_id', $prova->id)->where('turma_id', $data['turma_id'])->exists(), 404);
        }

        try {
            $arquivo = $gerador->gerar($prova, $data['turma_id'] ?? null, (int) ($data['brancos'] ?? 0), $request->user());
        } catch (\Throwable $e) {
            report($e);
            return ApiResponse::error('Não foi possível gerar os cartões.', [], 500);
        }

        // O vínculo pode ser revogado enquanto o processo gera o PDF.
        if (\App\Support\ContextoVinculo::presente($request->user())) {
            try {
                abort_unless(Permissao::pode($request->user(), 'cartao.imprimir') && $this->encontrarProva($request, $provaId), 403);
            } catch (\Throwable $e) { @unlink($arquivo); throw $e; }
        }
        $nome = 'cartoes-' . \Illuminate\Support\Str::slug($prova->titulo . ' ' . $prova->disciplina) . '.pdf';

        return response()->download($arquivo, $nome, ['Content-Type' => 'application/pdf'])->deleteFileAfterSend();
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $cartao = ContextoCorrecao::cartoes($request->user())->with('aluno:id,nome,matricula')->find($id);
        if (!$cartao || !$this->encontrarProva($request, $cartao->prova_id)) {
            return ApiResponse::notFound('Cartão não encontrado.');
        }

        $respostas = ContextoCorrecao::filtrar(DB::table('cartao_respostas'), $cartao->espaco_id)
            ->where('cartao_id', $id)
            ->orderBy('numero_questao')
            ->get()
            ->map(function ($r) {
                $r->alternativas_detectadas = json_decode($r->alternativas_detectadas, true) ?? [];
                return $r;
            });

        return ApiResponse::success(array_merge($cartao->toArray(), [
            'versao_correcao' => app(\App\Services\Omr\HistoricoCartao::class)->fingerprint($cartao),
            'nota_atual' => DB::table('notas')->where('cartao_id',$cartao->id)->value('nota_final'),
            'aluno_nome' => $cartao->aluno?->nome,
            'respostas'  => $respostas,
        ]));
    }

    public function vincularAluno(Request $request, int $id): JsonResponse
    {
        $cartao = ContextoCorrecao::cartoes($request->user())->find($id);
        if (!$cartao || !($prova = $this->encontrarProva($request, $cartao->prova_id))) {
            return ApiResponse::notFound('Cartão não encontrado.');
        }
        if ($bloqueio = Correcao::bloqueio($request->user(), $prova)) {
            return ApiResponse::error($bloqueio, ['motivo' => 'correcao_fechada'], 403);
        }

        $data = $request->validate([
            'aluno_id' => 'required|integer|exists:alunos,id',
        ]);

        return DB::transaction(function () use ($request, $id, $data, $prova) {
            if(Espacos::ativo()) DB::table('espacos')->where('id',$prova->espaco_id)->lockForUpdate()->first();
            Prova::whereKey($prova->id)->lockForUpdate()->firstOrFail();
            $usuario = $request->user()->fresh();
            abort_unless($usuario && $usuario->ativo, 404);
            $cartao = ContextoCorrecao::cartoes($usuario)->lockForUpdate()->findOrFail($id);
            abort_unless((int) $cartao->prova_id === (int) $prova->id, 409);
            abort_if(Correcao::bloqueio($usuario, $prova->fresh()), 403);
            abort_unless(ContextoCorrecao::alunos($prova, $request->user())->where('alunos.id', $data['aluno_id'])->exists(), 404);
            $outro=ContextoCorrecao::filtrar(Cartao::query(),$prova->espaco_id)->where('prova_id',$prova->id)->where('aluno_id',$data['aluno_id'])->where('id','!=',$id)->whereIn('status',['lido','ambiguo'])->lockForUpdate()->first();
            if($outro){
                $cartao->aluno_id=$data['aluno_id'];
                $leituras=DB::table('cartao_respostas')->where('cartao_id',$id)->orderBy('numero_questao')->get()->map(fn($l)=>[
                    'numero_questao'=>$l->numero_questao,'alternativa'=>$l->alternativa,'confianca'=>(float)$l->confianca,'ambigua'=>(bool)$l->ambigua,
                    'multiplas_marcacoes'=>false,'alternativas_detectadas'=>json_decode($l->alternativas_detectadas,true)??[]])->all();
                $envio=CartaoEnvio::where('cartao_id',$id)->lockForUpdate()->first();abort_unless($envio&&count($leituras)===(int)$prova->num_questoes,409,'Este aluno já possui correção. Envie outra foto para preparar uma tentativa.');
                app(\App\Services\Omr\HistoricoCartao::class)->propor($cartao,$outro,$envio,$request->user(),$leituras);
                return ApiResponse::success(['vinculado'=>false,'aguardando_confirmacao'=>true,'tentativa_id'=>$envio->fresh()->resultado['tentativa_id']]);
            }
            $cartao->update(['aluno_id' => $data['aluno_id']]);

            if ($cartao->status === 'lido') {
                CalculadoraNota::calcular($cartao->fresh(), $request->user()->id);
            }
            app(\App\Services\Omr\HistoricoCartao::class)->registrarAtual($cartao->fresh(),$request->user(),'vinculada');

            return ApiResponse::success(['vinculado' => true]);
        });
    }

    public function resolverAmbiguidade(Request $request, int $id): JsonResponse
    {
        $usuario = $request->user();

        $cartao = ContextoCorrecao::cartoes($request->user())->find($id);
        if (!$cartao || !($prova = $this->encontrarProva($request, $cartao->prova_id))) {
            return ApiResponse::notFound('Cartão não encontrado.');
        }
        if ($bloqueio = Correcao::bloqueio($usuario, $prova)) {
            return ApiResponse::error($bloqueio, ['motivo' => 'correcao_fechada'], 403);
        }

        $data = $request->validate([
            'numero_questao' => 'required|integer|min:1',
            'alternativa'    => 'required|string|in:A,B,C,D,E,' . implode(',', array_keys(self::DECISOES)),
        ]);

        return DB::transaction(function () use ($request, $id, $data, $prova) {
            Prova::whereKey($prova->id)->lockForUpdate()->firstOrFail();
            $usuario = $request->user()->fresh();
            abort_unless($usuario && $usuario->ativo, 404);
            $cartao = ContextoCorrecao::cartoes($usuario)->lockForUpdate()->findOrFail($id);
            abort_unless((int) $cartao->prova_id === (int) $prova->id, 409);
            abort_if(Correcao::bloqueio($usuario, $prova->fresh()), 403);
            $resposta = ContextoCorrecao::filtrar(DB::table('cartao_respostas'), $cartao->espaco_id)
                ->where('cartao_id', $id)
                ->where('numero_questao', $data['numero_questao'])
                ->first();

            if (!$resposta) {
                return ApiResponse::notFound('Questão não encontrada no cartão.');
            }

            $this->registrarDecisao($cartao, $resposta, $usuario->id, $data['alternativa']);

            $restamAmbiguas = ContextoCorrecao::filtrar(DB::table('cartao_respostas'), $cartao->espaco_id)
                ->where('cartao_id', $cartao->id)
                ->where('ambigua', true)
                ->exists();

            if (!$restamAmbiguas) {
                $cartao->update([
                    'status'        => 'lido',
                    'resolvido_por' => $usuario->id,
                    'resolvido_em'  => now(),
                ]);

                CalculadoraNota::calcular($cartao->fresh(), $request->user()->id);
                AtualizadorStatusProva::atualizar($cartao->prova);
            }

            app(\App\Services\Omr\HistoricoCartao::class)->registrarAtual($cartao->fresh(),$request->user(),'revisada','Conferência manual da questão '.$data['numero_questao']);
            return ApiResponse::success([
                'resolvido'      => true,
                'cartao_status'  => $cartao->fresh()->status,
            ]);
        });
    }

    public function revisar(Request $request, int $id): JsonResponse
    {
        $usuario = $request->user();

        $cartao = ContextoCorrecao::cartoes($request->user())->find($id);
        if (!$cartao || !($prova = $this->encontrarProva($request, $cartao->prova_id))) {
            return ApiResponse::notFound('Cartão não encontrado.');
        }
        if ($bloqueio = Correcao::bloqueio($usuario, $prova)) {
            return ApiResponse::error($bloqueio, ['motivo' => 'correcao_fechada'], 403);
        }

        if ($cartao->status !== 'lido') {
            return ApiResponse::error('Apenas cartões já lidos podem ser revisados.', [], 422);
        }

        $data = $request->validate([
            'numero_questao' => 'required|integer|min:1',
            'alternativa'    => 'required|string|in:A,B,C,D,E,' . implode(',', array_keys(self::DECISOES)),
        ]);

        return DB::transaction(function () use ($request, $id, $data, $prova) {
            Prova::whereKey($prova->id)->lockForUpdate()->firstOrFail();
            $usuario = $request->user()->fresh();
            abort_unless($usuario && $usuario->ativo, 404);
            $cartao = ContextoCorrecao::cartoes($usuario)->lockForUpdate()->findOrFail($id);
            abort_unless((int) $cartao->prova_id === (int) $prova->id, 409);
            abort_if(Correcao::bloqueio($usuario, $prova->fresh()), 403);
            $resposta = ContextoCorrecao::filtrar(DB::table('cartao_respostas'), $cartao->espaco_id)
                ->where('cartao_id', $id)
                ->where('numero_questao', $data['numero_questao'])
                ->first();

            if (!$resposta) {
                return ApiResponse::notFound('Questão não encontrada no cartão.');
            }

            $this->registrarDecisao($cartao, $resposta, $usuario->id, $data['alternativa']);

            $cartao->update([
                'revisado_por' => $usuario->id,
                'revisado_em'  => now(),
            ]);

            CalculadoraNota::calcular($cartao->fresh(), $request->user()->id);

            app(\App\Services\Omr\HistoricoCartao::class)->registrarAtual($cartao->fresh(),$request->user(),'revisada','Revisão manual da questão '.$data['numero_questao']);
            return ApiResponse::success(['revisado' => true, 'cartao_status' => 'lido']);
        });
    }

    /** Em branco e anulada ficam sem alternativa (não pontuam); o log guarda quem decidiu o quê e quando. */
    private function registrarDecisao(Cartao $cartao, object $resposta, int $usuarioId, string $alternativa): void
    {
        if(!$cartao->tentativa_id)app(\App\Services\Omr\HistoricoCartao::class)->registrarAtual($cartao,null,'registro_anterior');
        $codigo = self::DECISOES[$alternativa] ?? null;

        ContextoCorrecao::filtrar(DB::table('cartao_respostas'), $cartao->espaco_id)->where('id', $resposta->id)->update([
            'alternativa' => $codigo ? null : $alternativa,
            'ambigua'     => false,
        ]);

        DB::table('ambiguidade_logs')->insert([
            'cartao_id'             => $cartao->id,
            'cartao_resposta_id'    => $resposta->id,
            'usuario_id'            => $usuarioId,
            'alternativa_escolhida' => $codigo ?? $alternativa,
            'created_at'            => now(),
        ] + ContextoCorrecao::autoria($cartao->espaco_id, $usuarioId));
    }

    /** Fila da tela de revisão: as questões pendentes da prova ou todas as de um cartão (?cartao=). */
    public function revisao(Request $request, int $provaId, ImagensCartao $imagens): JsonResponse
    {
        $prova = $this->encontrarProva($request, $provaId);
        if (!$prova) {
            return ApiResponse::notFound('Prova não encontrada.');
        }

        $cartaoId = $request->integer('cartao');
        $disco    = Storage::disk('public');

        $itens = DB::table('cartao_respostas as r')
            ->join('cartoes as c', 'c.id', '=', 'r.cartao_id')
            ->leftJoin('alunos as a', 'a.id', '=', 'c.aluno_id')
            ->leftJoin('turmas as t', 't.id', '=', 'a.turma_id')
            ->where('c.prova_id', $provaId)
            ->when(Espacos::ativo(), fn ($q) => $q->whereIn('c.id', ContextoCorrecao::cartoes($request->user())->select('cartoes.id'))->whereColumn('r.espaco_id', 'c.espaco_id'))
            ->when(
                $cartaoId,
                fn ($q) => $q->where('c.id', $cartaoId),
                fn ($q) => $q->where('c.status', 'ambiguo')->where('r.ambigua', true)
            )
            ->orderBy('a.nome')->orderBy('c.id')->orderBy('r.numero_questao')
            ->get(['c.id as cartao_id', 'c.status', 'c.imagem_url', 'a.nome as aluno_nome', 't.nome as turma',
                   'r.numero_questao', 'r.alternativa', 'r.ambigua', 'r.alternativas_detectadas']);
        $urls=$imagens->urlsDaProva($prova,$itens->pluck('cartao_id')->unique()->all(),['original','endireitada'],$request->user());
        $itens=$itens->map(function ($r) use ($prova, $urls, $disco) {
                $temEndireitada = $disco->exists(ImagensCartao::disponivel($r->cartao_id, 'endireitada', $prova->espaco_id));
                return [
                    'cartao_id'       => $r->cartao_id,
                    'status'          => $r->status,
                    'aluno_nome'      => $r->aluno_nome,
                    'turma'           => $r->turma,
                    'numero_questao'  => $r->numero_questao,
                    'alternativa'     => $r->alternativa,
                    'ambigua'         => (bool) $r->ambigua,
                    'detectadas'      => array_column(json_decode($r->alternativas_detectadas ?? '[]', true) ?? [], 'alternativa'),
                    'foto_url'        => $r->imagem_url ? $urls[$r->cartao_id]['original'] : null,
                    'endireitada_url' => $temEndireitada ? $urls[$r->cartao_id]['endireitada'] : null,
                    'recorte'         => ImagensCartao::recorte($r->numero_questao, $prova->num_questoes, $prova->num_alternativas),
                ];
            })
            ->values();

        return ApiResponse::success(['itens' => $itens]);
    }

    /** Fecha a correção: a partir daqui só a supervisão altera, exclui ou inclui cartões. */
    public function fecharCorrecao(Request $request, int $provaId): JsonResponse
    {
        $prova = $this->encontrarProva($request, $provaId);
        if (!$prova) {
            return ApiResponse::notFound('Prova não encontrada.');
        }
        if (!Correcao::fechada($prova)) {
            $prova->update(['correcao_fechada_em' => now(), 'correcao_fechada_por' => $request->user()->id]);
        }

        return ApiResponse::success(Correcao::situacao($prova->fresh()), 'Correção fechada.');
    }

    /** Reabre a correção (só supervisão): professor/corretor voltam a enviar, excluir e alterar. */
    public function reabrirCorrecao(Request $request, int $provaId): JsonResponse
    {
        $prova = $this->encontrarProva($request, $provaId);
        if (!$prova) {
            return ApiResponse::notFound('Prova não encontrada.');
        }
        $prova->update(['correcao_fechada_em' => null, 'correcao_fechada_por' => null]);

        return ApiResponse::success(Correcao::situacao($prova->fresh()), 'Correção reaberta.');
    }
}
