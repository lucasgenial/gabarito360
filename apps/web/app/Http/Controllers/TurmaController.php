<?php

namespace App\Http\Controllers;

use App\Services\ApiClient;
use Illuminate\Http\Request;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\View\View;

class TurmaController extends Controller
{
    public function __construct(private ApiClient $api) {}

    public function index(Request $request): View
    {
        $resp = $this->api->get('/v1/turmas', $request->only('busca', 'escola_id', 'ano_letivo', 'ativo'));

        $turmas  = $resp->successful() ? $resp->json('data.turmas') : [];
        $kpis    = $resp->successful() ? $resp->json('data.kpis')   : [];

        $escolas = \App\Support\Acesso::pessoal() ? [] : ($this->api->get('/v1/escolas')->json('data.escolas') ?? []);

        return view('turmas.index', [
            'erroLista' => !$resp->successful(),
            'haFiltros' => collect(['busca', 'escola_id', 'ano_letivo', 'ativo'])->contains(fn (string $campo) => $request->filled($campo)),
            'turmas'     => $turmas,
            'kpis'       => $kpis,
            'escolas'    => $escolas,
            'busca'      => $request->query('busca', ''),
            'escola_id'  => $request->query('escola_id', ''),
            'ano_letivo' => $request->query('ano_letivo', ''),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'escola_id'  => \App\Support\Acesso::pessoal() ? 'prohibited' : 'required|integer',
            'nome'       => 'required|string|max:50',
            'serie'      => 'required|string|max:50',
            'turno'      => 'required|string',
            'ano_letivo' => 'required|integer',
        ]);

        $resp = $this->api->post('/v1/turmas', $request->only(
            'escola_id','nome','serie','turno','ano_letivo','sala','capacidade','etapa_ensino','observacoes'
        ));

        if ($resp->failed()) {
            return back()->withInput()
                ->with('erro', $resp->json('message') ?? 'Não foi possível criar a turma.');
        }

        return redirect()->route('turmas.index')->with('sucesso', 'Turma criada.');
    }

    public function show(int $id): View|\Illuminate\Http\Response
    {
        try {
            $resp = $this->api->get("/v1/turmas/{$id}");
        } catch (ConnectionException) {
            return $this->indisponivel();
        }

        if ($resp->serverError() || $resp->status() === 429) return $this->indisponivel();

        if ($resp->failed()) {
            abort(404, 'Turma não encontrada.');
        }

        if (\App\Support\Acesso::administrativo()) return view('turmas.administrativo', ['turma' => $resp->json('data')]);

        $filtros = request()->validate(['secao'=>'nullable|in:visao-geral,alunos,disciplinas,provas,acesso',
            'catalogo_busca'=>'nullable|string|max:100','catalogo_pagina'=>'nullable|integer|min:1|max:10000']);
        $escolas = []; $professores = []; $todosProfessores = []; $acessoErro = false; $escolasErro = false;
        if (!\App\Support\Acesso::pessoal()) {
            try {
                $r = $this->api->get("/v1/turmas/{$id}/professores");
                $acessoErro = $r->failed(); $professores = $r->successful() ? ($r->json('data.professores') ?? []) : [];
                if (\App\Support\Acesso::pode('turma.vincular_professor')) {
                    $r = $this->api->get('/v1/usuarios', ['perfil'=>'professor']);
                    $acessoErro = $acessoErro || $r->failed(); $todosProfessores = $r->successful() ? ($r->json('data.usuarios') ?? []) : [];
                }
            } catch (ConnectionException) { $acessoErro = true; }
        }

        $dc1Visivel = config('disciplinas.ativo') && session('auth_vinculos') && (\App\Support\ContextoWeb::atual()['espaco']['tipo'] ?? '') === 'escola'
            && in_array(\App\Support\Acesso::perfil(), ['coordenador','dir_escolar','vice_dir_escolar','professor'], true) && \App\Support\Acesso::pode('turma.ver');
        $dc1Dados = null; $dc1Erro = false; $dc1Retorno = [];
        if ($dc1Visivel) {
            try {
                $dc1 = $this->api->get("/v1/turmas/{$id}/disciplinas"); $dc1Erro = $dc1->failed(); $dc1Dados = $dc1->json('data');
                if ((int)request('retorno_turma') === $id && request()->filled('retorno_componente') && \App\Support\Acesso::pode('disciplina.gerir')) {
                    $campos = request()->validate(['retorno_turma'=>'required|integer|min:1','retorno_componente'=>'required|integer|min:1',
                        'rascunho_inicio'=>'nullable|date_format:Y-m-d','rascunho_fim'=>'nullable|date_format:Y-m-d','rascunho_papel'=>'nullable|in:titular,codocencia,substituicao','rascunho_vinculo'=>'nullable|integer|min:1','rascunho_chave'=>'nullable|uuid']);
                    if ($this->api->get("/v1/turmas/{$id}/disciplinas/".$campos['retorno_componente'])->successful()) $dc1Retorno = array_filter($campos, fn($v)=>$v!==null);
                }
            } catch (\Illuminate\Http\Client\ConnectionException) { $dc1Erro = true; }
        }

        $opcoesDocentes=null; $opcoesDocentesErro=false;
        if (!$dc1Erro && ($dc1Dados['atribuir']??false) && \App\Support\Acesso::perfil()==='coordenador' && \App\Support\Acesso::pode('disciplina.gerir')) {
            try { $r=$this->api->get("/v1/turmas/$id/vinculo-docente/opcoes"); $opcoesDocentesErro=$r->failed(); if(!$opcoesDocentesErro)$opcoesDocentes=$r->json('data'); }
            catch(ConnectionException){$opcoesDocentesErro=true;}
        }

        $painel=null; $painelErro=false; $painelErros=[]; $painelAplicado=[];
        if (\App\Support\Acesso::pode('relatorio.turma')) {
            $regras=$this->regrasFiltrosPainel();
            $v=\Illuminate\Support\Facades\Validator::make(request()->only(array_keys($regras)),$regras,$this->mensagensFiltrosPainel());
            if($v->fails()){
                $painelErros=$v->errors()->toArray();
                $anterior=request('painel_anterior',[]);
                $anterior=\Illuminate\Support\Facades\Validator::make(is_array($anterior)?$anterior:[],$regras,$this->mensagensFiltrosPainel());
                $painelAplicado=$anterior->fails()?[]:$anterior->validated();
            }else{$painelAplicado=$v->validated();}
            $recorte=$this->mapearFiltrosPainel($painelAplicado);
            try { $r=$this->api->get("/v1/turmas/$id/painel",$recorte); $painelErro=$r->failed(); if(!$painelErro)$painel=$r->json('data'); }
            catch(ConnectionException){$painelErro=true;}
        }
        return view('turmas.show', [
            'painel'=>$painel,'painelErro'=>$painelErro,'painelErros'=>$painelErros,'painelAplicado'=>$painelAplicado,
            'secaoInicial'=>$filtros['secao']??'visao-geral', 'opcoesDocentes'=>$opcoesDocentes,'opcoesDocentesErro'=>$opcoesDocentesErro,
            'acessoErro'=>$acessoErro,'escolasErro'=>$escolasErro,
            'turma'            => $resp->json('data'),
            'escolas'          => $escolas,
            'professores'      => $professores,
            'todosProfessores' => $todosProfessores,
            'dc1Visivel' => $dc1Visivel, 'dc1Dados' => $dc1Dados, 'dc1Erro' => $dc1Erro, 'dc1Retorno' => $dc1Retorno,
        ]);
    }

    private function indisponivel(): \Illuminate\Http\Response
    {
        return response()->view('turmas.indisponivel', [], 503)->header('Retry-After', '30');
    }

    public function vincularProfessor(Request $request, int $id)
    {
        $retorno = $this->retornoAcesso($request, $id);
        if (session('auth_vinculos')) $request->validate(['vinculo_id' => 'required|integer|min:1']);
        $resp = $this->api->post("/v1/turmas/{$id}/professores", $request->only('usuario_id', 'vinculo_id'));

        if ($resp->failed()) {
            return redirect()->route('turmas.show', $retorno)->withInput()->with('erro', $resp->json('message') ?? 'Não foi possível conceder acesso à turma.');
        }

        return redirect()->route('turmas.show', $retorno)->with('sucesso', 'Acesso à turma concedido. A atribuição de disciplina é uma ação separada.');
    }

    public function desvincularProfessor(int $id, int $usuarioId)
    {
        $retorno = $this->retornoAcesso(request(), $id);
        $resp = $this->api->delete("/v1/turmas/{$id}/professores/{$usuarioId}", session('auth_vinculos') ? request()->validate(['vinculo_id' => 'required|integer|min:1']) : []);

        if ($resp->failed()) {
            return redirect()->route('turmas.show', $retorno)->with('erro', 'Não foi possível retirar o acesso à turma.');
        }

        return redirect()->route('turmas.show', $retorno)->with('sucesso', 'Acesso à turma retirado. O histórico foi preservado.');
    }

    private function retornoAcesso(Request $request, int $id): array
    {
        $destino = ['id'=>$id,'secao'=>'acesso'];
        // A caixa pertence à entrada de origem nesta sessão/revisão, nunca à última turma global.
        $origem = $request->attributes->get('ur4_retorno');
        if (($origem['nome']??null)!=='turmas.show' || (int)($origem['parametros']['id']??0)!==$id) return $destino;
        $campos = array_intersect_key($origem['consulta'], array_flip(['retorno_turma','retorno_componente','rascunho_inicio','rascunho_fim','rascunho_papel','rascunho_vinculo','rascunho_chave']));
        if ((int)($campos['retorno_turma']??0)!==$id || empty($campos['retorno_componente'])) return $destino;
        $v = \Illuminate\Support\Facades\Validator::make($campos, ['retorno_turma'=>'required|integer|min:1','retorno_componente'=>'required|integer|min:1',
            'rascunho_inicio'=>'nullable|date_format:Y-m-d','rascunho_fim'=>'nullable|date_format:Y-m-d','rascunho_papel'=>'nullable|in:titular,codocencia,substituicao','rascunho_vinculo'=>'nullable|integer|min:1','rascunho_chave'=>'nullable|uuid']);
        return $v->fails() ? $destino : $destino + $v->validated();
    }

    public function update(Request $request, int $id)
    {
        if (\App\Support\Acesso::pessoal()) $request->validate(['escola_id'=>'prohibited']);
        try { $request->validate([
            'nome'  => 'required|string|max:50',
            'serie' => 'required|string|max:50',
            'turno' => 'required|in:manha,tarde,noite,integral',
            'ano_letivo'=>'sometimes|integer|min:2020|max:2099',
            'sala'=>'sometimes|nullable|string|max:50',
            'capacidade'=>'sometimes|nullable|integer|min:1|max:1000',
            'etapa_ensino'=>'sometimes|nullable|in:educacao_infantil,ensino_fundamental,ensino_medio,educacao_profissional,ensino_superior,eja,outra',
            'observacoes'=>'sometimes|nullable|string|max:1000',
        ]); } catch (\Illuminate\Validation\ValidationException $e) { $e->redirectTo(route('turmas.edit',$id));throw $e; }

        $resp = $this->api->put("/v1/turmas/{$id}", $request->only(
            'escola_id','nome','serie','turno','ano_letivo','sala','capacidade','etapa_ensino','observacoes'
        ));

        if ($resp->failed()) {
            return redirect()->route('turmas.edit',$id)->withInput()->withErrors($resp->json('errors')??[])
                ->with('erro', $resp->json('message') ?? 'Não foi possível atualizar a turma.');
        }

        return redirect()->route('turmas.show', $id)->with('sucesso', 'Turma atualizada.');
    }

    public function edit(int $id): View
    {
        abort_unless(\App\Support\Acesso::pode('turma.gerir'), 403);
        $r = $this->api->get("/v1/turmas/{$id}");
        abort_unless($r->successful(), $r->serverError() ? 503 : 404);
        return view('turmas.edit', ['turma'=>$r->json('data')]);
    }

    public function atribuir(Request $request, int $id)
    {
        abort_unless(\App\Support\Acesso::perfil()==='coordenador' && \App\Support\Acesso::pode('disciplina.gerir') && \App\Support\Acesso::pode('turma.vincular_professor'),403);
        $retorno = ['id'=>$id,'secao'=>'disciplinas'];
        try { $d=$request->validate(['disciplina_id'=>'required|integer|min:1','professor_vinculo_id'=>'required|integer|min:1','chave_idempotencia'=>'required|uuid']); }
        catch (\Illuminate\Validation\ValidationException $e) { $e->redirectTo(route('turmas.show',$retorno)); throw $e; }
        try { $r=$this->api->post("/v1/turmas/$id/vinculos-docentes",$d); }
        catch (ConnectionException) { return redirect()->route('turmas.show',$retorno)->withInput()->with('erro','Não foi possível confirmar a atribuição. Confira o histórico antes de reenviar.'); }
        abort_if(in_array($r->status(),[403,404],true),$r->status());
        if ($r->failed()) return redirect()->route('turmas.show',$retorno)->withInput()->withErrors($r->json('errors')??[])->with('erro',$r->json('message')??'Não foi possível registrar a atribuição.');
        return redirect()->route('turmas.show',$retorno)->with('sucesso','Professor atribuído à disciplina nesta turma.');
    }

    private function regrasFiltrosPainel(): array
    {
        return ['painel_avaliacao'=>'nullable|integer|min:1','painel_disciplina'=>'nullable|integer|min:1','painel_professor'=>'nullable|integer|min:1','painel_inicio'=>'nullable|date_format:Y-m-d','painel_fim'=>'nullable|date_format:Y-m-d|after_or_equal:painel_inicio'];
    }

    private function mensagensFiltrosPainel(): array
    {
        return ['*.integer'=>'Escolha uma opção válida.','*.min'=>'Escolha uma opção válida.','*.date_format'=>'Informe uma data válida.','painel_fim.after_or_equal'=>'A data final deve ser igual ou posterior à data inicial.'];
    }

    private function mapearFiltrosPainel(array $d): array
    {
        $mapa=['painel_avaliacao'=>'analise_prova_id','painel_disciplina'=>'disciplina_id','painel_professor'=>'professor_id','painel_inicio'=>'inicio','painel_fim'=>'fim'];$saida=[];
        foreach($d as $k=>$v)if(isset($mapa[$k])&&$v!==null&&$v!=='')$saida[$mapa[$k]]=$v;
        return $saida;
    }

    private function filtrosPainel(Request $r): array
    {
        return $this->mapearFiltrosPainel($r->validate($this->regrasFiltrosPainel(),$this->mensagensFiltrosPainel()));
    }

    public function painelPdf(Request $r,int $id)
    {
        abort_unless(\App\Support\Acesso::pode('relatorio.turma'),403);
        $d=$this->filtrosPainel($r)+$r->validate(['orientacao'=>'required|in:retrato,paisagem']);
        $resp=$this->api->getArquivo("/v1/turmas/$id/painel/pdf",$d);
        abort_unless($resp->successful()&&str_contains((string)$resp->header('Content-Type'),'pdf'),$resp->failed()?$resp->status():502,'Não foi possível gerar o documento.');
        return response($resp->body(),200,['Content-Type'=>'application/pdf','Content-Disposition'=>($r->boolean('baixar')?'attachment':'inline').'; filename="painel-turma.pdf"','Cache-Control'=>'private, no-store','X-Content-Type-Options'=>'nosniff']);
    }

    /** Estado explícito, revisão monotônica e recibo idempotente; toggle é só compatibilidade. */
    public function arquivamento(Request $r, int $id)
    {
        abort_unless(\App\Support\Acesso::pode('turma.desativar'),403);
        $d=$r->validate(['arquivado'=>'required|boolean','esperado_ativo'=>'required|boolean','versao'=>'required|integer|min:0','chave'=>'required|uuid','motivo'=>'nullable|string|max:500']);
        try { $resp=$this->api->put("/v1/turmas/{$id}/arquivamento",$d); }
        catch (ConnectionException) { return back()->with('erro','Não foi possível confirmar o arquivamento. Confira o estado atual antes de reenviar.'); }
        abort_if(in_array($resp->status(),[403,404],true),$resp->status());
        if($resp->failed())return back()->with('erro',$resp->json('message')??'Não foi possível confirmar o arquivamento. Atualize a página.');
        $estado=$resp->json('data');
        if(!is_array($estado)||!is_bool($estado['ativo']??null)||!is_bool($estado['arquivado']??null)
            ||$estado['ativo']===$estado['arquivado']||!is_int($estado['versao']??null)||$estado['versao']<0
            ||!is_bool($estado['alterado']??null)||!is_bool($estado['repetido']??null)){
            return back()->with('erro','Não foi possível confirmar a resposta do arquivamento. Confira o estado atual antes de reenviar.');
        }
        return back()->with('sucesso',$resp->json('data.arquivado')?'O registro foi arquivado. O histórico foi preservado.':'O registro foi desarquivado.');
    }

    public function imagem(Request $r,int $id)
    {
        abort_unless(\App\Support\Acesso::pode('turma.gerir'),403);
        $r->validate(['tipo'=>'required|in:avatar,foto','avatar_id'=>'nullable|integer|between:1,36','foto'=>'required_if:tipo,foto|file|image|mimes:jpg,jpeg,png,webp|max:2048|dimensions:max_width=4096,max_height=4096']);
        try{$resp=$r->input('tipo')==='foto'?$this->api->postFile('/v1/turmas/'.$id.'/imagem','foto',$r->file('foto'),['tipo'=>'foto']):$this->api->post('/v1/turmas/'.$id.'/imagem',$r->only('tipo','avatar_id'));}
        catch(ConnectionException){return response()->json(['message'=>'Não foi possível confirmar a atualização. Atualize a página para conferir.'],503);}
        if($resp->status()===401)return \App\Support\SessaoWeb::encerrar($r);
        if($resp->status()===429){$segundos=max(1,min(60,(int)$resp->header('Retry-After')?:60));return response()->json(['message'=>'Muitas tentativas seguidas. Aguarde alguns instantes.'],429)->header('Retry-After',(string)$segundos);}
        if(!$resp->successful())return response()->json(['message'=>$resp->json('message')??'Não foi possível salvar a imagem.'],$resp->status()>=500?503:($resp->status()===403?403:($resp->status()===404?404:422)));
        $d=$resp->json('data');
        if(!is_array($d)||!is_bool($d['tem_foto']??null)||!is_int($d['imagem_versao']??null)||$d['imagem_versao']<1||!array_key_exists('avatar_id',$d)||($d['avatar_id']!==null&&(!is_int($d['avatar_id'])||$d['avatar_id']<1||$d['avatar_id']>36)))return response()->json(['message'=>'Não foi possível confirmar a imagem. Atualize a página para conferir.'],502);
        $d=array_intersect_key($d,array_flip(['avatar_id','tem_foto','imagem_versao']));$d['foto_url']=\App\Support\NavegacaoWeb::destino('turmas.foto',['id'=>$id,'v'=>$d['imagem_versao']]);
        return response()->json(['data'=>$d]);
    }
    public function foto(int $id)
    {
        abort_unless(\App\Support\Acesso::pode('turma.ver'),403);$r=$this->api->getArquivo('/v1/turmas/'.$id.'/foto');
        if($r->status()===401)return \App\Support\SessaoWeb::encerrar(request());
        abort_unless($r->successful()&&str_contains(strtolower((string)$r->header('Content-Type')),'image/webp'),$r->status()===403?403:404);
        return response($r->body(),200,['Content-Type'=>'image/webp','Cache-Control'=>'private, no-store','X-Content-Type-Options'=>'nosniff']);
    }

    public function toggle(int $id)
    {
        $resp = $this->api->post("/v1/turmas/{$id}/toggle");

        if ($resp->failed()) {
            return back()->with('erro', 'Não foi possível alterar o status da turma.');
        }

        $status = $resp->json('data.ativo') ? 'ativada' : 'desativada';
        return back()->with('sucesso', "Turma {$status}.");
    }
}
