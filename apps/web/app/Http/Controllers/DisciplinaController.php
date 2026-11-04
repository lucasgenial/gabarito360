<?php

namespace App\Http\Controllers;

use App\Services\ApiClient;
use App\Support\{Acesso, ContextoWeb};
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;

class DisciplinaController extends Controller
{
    public function __construct(protected ApiClient $api) {}

    protected function autorizar(bool $gerir = false): void
    {
        abort_unless(config('disciplinas.ativo'), 404);
        abort_unless(session('auth_vinculos') && (ContextoWeb::atual()['espaco']['tipo'] ?? '') === 'escola'
            && in_array(Acesso::perfil(), ['coordenador', 'dir_escolar', 'vice_dir_escolar', 'professor'], true) && Acesso::pode('turma.ver'), 403);
        if ($gerir) abort_unless(Acesso::perfil() === 'coordenador' && Acesso::pode('disciplina.gerir'), 403);
    }

    protected function ler(string $path, array $query = []): array
    {
        try { $r = $this->api->get('/v1/'.$path, $query); }
        catch (ConnectionException) { abort(503, 'Não foi possível consultar as disciplinas. Tente novamente.'); }
        abort_unless($r->successful(), in_array($r->status(), [403, 404, 422], true) ? $r->status() : 503, 'Não foi possível consultar estes dados.');
        return ['dados' => $r->json('data'), 'meta' => $r->json('meta') ?? []];
    }

    protected function retorno(Request $r): array
    {
        $d = $r->validate(['retorno_turma' => 'nullable|integer|min:1', 'retorno_componente' => 'nullable|integer|min:1',
            'rascunho_inicio' => 'nullable|date_format:Y-m-d', 'rascunho_fim' => 'nullable|date_format:Y-m-d', 'rascunho_papel' => 'nullable|in:titular,codocencia,substituicao', 'rascunho_vinculo' => 'nullable|integer|min:1', 'rascunho_chave' => 'nullable|uuid']);
        if (!empty($d['retorno_turma'])) {
            $p = 'turmas/'.$d['retorno_turma'].'/disciplinas';
            if (!empty($d['retorno_componente'])) $p .= '/'.$d['retorno_componente'];
            $this->ler($p);
        }
        return array_filter($d, fn ($v) => $v !== null);
    }

    public function index(Request $r)
    {
        if ($resposta = $this->rever($r)) return $resposta;
        $this->autorizar();
        $f = $r->validate(['busca' => 'nullable|string|max:100', 'ativo' => 'nullable|in:0,1', 'page' => 'nullable|integer|min:1|max:10000']);
        return view('disciplinas.index', $this->ler('disciplinas', array_filter($f, fn ($v) => $v !== null && $v !== '')) + ['f' => $f, 'retorno' => $this->retorno($r)]);
    }

    public function create(Request $r)
    {
        if ($resposta = $this->rever($r)) return $resposta;
        $this->autorizar(true); $retorno = $this->retorno($r);
        $f = $r->validate(['origem' => 'nullable|in:referencia,propria', 'busca' => 'nullable|string|max:100', 'referencia' => 'nullable|string|max:50']);
        $op = $this->ler('disciplinas/opcoes')['dados'];
        abort_unless($op['gerir'], 403);
        $referencias = collect($op['referencias']); $selecionada = $referencias->firstWhere('chave', $f['referencia'] ?? '');
        abort_if(!empty($f['referencia']) && !$selecionada, 404);
        if ($busca = trim($f['busca'] ?? '')) $referencias = $referencias->filter(fn ($i) => str_contains(\Illuminate\Support\Str::lower(\Illuminate\Support\Str::ascii($i['nome'])), \Illuminate\Support\Str::lower(\Illuminate\Support\Str::ascii($busca))));
        return view('disciplinas.form', ['disciplina' => null, 'f' => $f, 'referencias' => $referencias->all(), 'selecionada' => $selecionada, 'retorno' => $retorno, 'areas' => $op['areas'] ?? []]);
    }

    public function edit(Request $r, int $id)
    {
        if ($resposta = $this->rever($r)) return $resposta;
        $this->autorizar(true); $d = $this->ler('disciplinas/'.$id)['dados']; abort_unless($d['gerir'], 403);
        $op = $this->ler('disciplinas/opcoes', ['disciplina_id' => $id])['dados'];
        return view('disciplinas.form', ['disciplina' => $d['disciplina'], 'retorno' => $this->retorno($r), 'f' => [], 'referencias' => [], 'selecionada' => null, 'areas' => $op['areas'] ?? []]);
    }

    public function show(Request $r, int $id)
    {
        if ($resposta = $this->rever($r)) return $resposta;
        $this->autorizar(); $retorno = $this->retorno($r); $d = $this->ler('disciplinas/'.$id)['dados'];
        $f = $r->validate(['busca' => 'nullable|string|max:100', 'page' => 'nullable|integer|min:1|max:10000']);
        $candidatos = $d['gerir'] ? $this->ler('disciplinas/professores', array_filter($f, fn ($v) => $v !== null && $v !== '')) : ['dados' => ['professores' => []], 'meta' => []];
        return view('disciplinas.show', ['dados' => $d, 'candidatos' => $candidatos['dados']['professores'], 'meta' => $candidatos['meta'], 'retorno' => $retorno, 'f' => $f]);
    }

    public function store(Request $r) { return $this->salvar($r); }
    public function update(Request $r, int $id) { return $this->salvar($r, $id); }

    private function salvar(Request $r, ?int $id = null)
    {
        $this->autorizar(true); $retorno = $this->retorno($r);
        if ($r->boolean('area_selecao')) $r->merge(['area_ids' => $r->input('area_ids', [])]);
        $d = $r->validate(['nome' => 'required|string|max:100', 'sigla' => 'nullable|string|max:20', 'ativo' => 'required|in:0,1', 'referencia' => ($id ? 'prohibited' : 'nullable|string|max:50'), 'versao' => ($id ? 'required|integer|min:1' : 'prohibited')]);
        $d += $r->validate(['area_ids' => 'sometimes|array|max:200', 'area_ids.*' => 'required|integer|min:1|distinct']);
        $resp = $this->escrever($id ? 'put' : 'post', 'disciplinas'.($id ? '/'.$id : ''), $d);
        if (!$resp || $resp->failed()) return $this->erro($r, $resp);
        return redirect()->route('disciplinas.show', ['id' => $resp->json('data.id')] + $retorno)->with('sucesso', $id ? 'Disciplina atualizada.' : 'Disciplina adicionada à escola.');
    }

    public function indicar(Request $r, int $id)
    {
        $this->autorizar(true); $this->retorno($r);
        $d = $r->validate(['vinculo_id' => 'required|integer|min:1', 'ativo' => 'required|in:0,1', 'versao' => 'nullable|integer|min:1']);
        $resp = $this->escrever('post', "disciplinas/$id/professores", $d);
        if (!$resp || $resp->failed()) return $this->erro($r, $resp);
        return back()->with('sucesso', $d['ativo'] ? 'Professor adicionado à disciplina.' : 'Indicação retirada. O histórico foi preservado.');
    }

    public function componente(Request $r, int $turma, int $componente)
    {
        if ($resposta = $this->rever($r)) return $resposta;
        $this->autorizar(); $d = $this->ler("turmas/$turma/disciplinas/$componente")['dados']; $t = $this->ler("turmas/$turma/disciplinas")['dados']['turma'];
        return view('disciplinas.componente', ['dados' => $d, 'turma' => $t, 'retorno' => $this->retorno($r)]);
    }

    public function adicionar(Request $r, int $turma)
    {
        $this->autorizar(true); $d = $r->validate(['disciplina_id' => 'required|integer|min:1']);
        $resp = $this->escrever('post', "turmas/$turma/disciplinas", $d);
        if (!$resp || $resp->failed()) return $this->erro($r, $resp);
        return redirect()->route('disciplinas.componente', ['turma' => $turma, 'componente' => $resp->json('data.id')])->with('sucesso', 'Disciplina vinculada à turma. Confira a situação e as atribuições abaixo.');
    }

    public function situacao(Request $r, int $turma, int $componente)
    {
        $this->autorizar(true); $d = $r->validate(['ativo'=>'required|in:0,1','versao'=>'required|integer|min:1']);
        $resp = $this->escrever('post', "turmas/$turma/disciplinas/$componente/situacao", $d);
        if (!$resp || $resp->failed()) return $this->erro($r, $resp);
        return back()->with('sucesso', $d['ativo'] ? 'Disciplina reativada nesta turma.' : 'Disciplina inativada somente nesta turma. O histórico foi preservado.');
    }

    public function atribuir(Request $r, int $turma, int $componente)
    {
        $this->autorizar(true);
        $d = $r->validate(['vinculo_id' => 'required|integer|min:1', 'inicio_em' => 'required|date_format:Y-m-d', 'fim_em' => 'nullable|date_format:Y-m-d|after:inicio_em', 'papel' => 'required|in:titular,codocencia,substituicao', 'chave' => 'required|uuid', 'versao' => 'required|integer|min:1']);
        $resp = $this->escrever('post', "turmas/$turma/disciplinas/$componente/atribuicoes", $d);
        if (!$resp || $resp->failed()) return $this->erro($r, $resp);
        return redirect()->route('disciplinas.componente', ['turma'=>$turma, 'componente'=>$componente])->with('sucesso', 'Atribuição registrada.');
    }

    public function encerrar(Request $r, int $turma, int $componente, int $atribuicao)
    {
        $this->autorizar(true); $d = $r->validate(['versao' => 'required|integer|min:1', 'acao' => 'required|in:cancelar,encerrar']);
        $resp = $this->escrever('post', "turmas/$turma/disciplinas/$componente/atribuicoes/$atribuicao/encerrar", $d);
        if (!$resp || $resp->failed()) return $this->erro($r, $resp);
        return back()->with('sucesso', $d['acao'] === 'cancelar' ? 'Atribuição futura cancelada.' : 'Atribuição encerrada a partir de hoje.');
    }

    protected function escrever(string $metodo, string $path, array $dados)
    {
        try { return $this->api->$metodo('/v1/'.$path, $dados); }
        catch (ConnectionException) { return null; }
    }

    protected function erro(Request $r, $resp)
    {
        if ($resp) abort_if(in_array($resp->status(), [403, 404], true), $resp->status());
        if ($resp?->status() === 409) {
            $url = parse_url(url()->previous());
            if (($url['host'] ?? $r->getHost()) === $r->getHost()) session()->put($this->chaveRascunho($url['path'] ?? '/'), $r->only('nome','sigla','ativo','area_ids','area_selecao','vinculo_id','inicio_em','fim_em','papel'));
        }
        return back()->withInput()->withErrors($resp?->json('errors') ?? [])->with('conflito_dc1', $resp?->status() === 409)
            ->with('erro', $resp && in_array($resp->status(), [409,422], true) ? $resp->json('message') : 'Não foi possível confirmar a operação. Atualize os dados antes de tentar novamente.');
    }

    private function chaveRascunho(string $path): string
    {
        return 'dc1_rascunho_'.hash('sha256', (ContextoWeb::id() ?? '').'|'.$path);
    }

    protected function rever(Request $r)
    {
        if (!$r->boolean('rever')) return null;
        $this->autorizar(true);
        $d = session()->pull($this->chaveRascunho('/'.$r->path()), []);
        session()->forget('conflito_dc1');
        return redirect()->to($r->fullUrlWithoutQuery('rever'))->withInput($d)->with('sucesso', 'Estado atualizado. Confira seu preenchimento e confirme novamente.');
    }
}
