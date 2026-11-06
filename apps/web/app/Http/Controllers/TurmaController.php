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
            'escola_id','nome','serie','turno','ano_letivo'
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

        $escolas     = \App\Support\Acesso::pessoal() ? [] : ($this->api->get('/v1/escolas')->json('data.escolas') ?? []);
        $professores = \App\Support\Acesso::pessoal() ? [] : ($this->api->get("/v1/turmas/{$id}/professores")->json('data.professores') ?? []);
        $todosProfessores = \App\Support\Acesso::pessoal() ? [] : ($this->api->get('/v1/usuarios', ['perfil' => 'professor'])->json('data.usuarios') ?? []);

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

        return view('turmas.show', [
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
        if (session('auth_vinculos')) $request->validate(['vinculo_id' => 'required|integer|min:1']);
        $resp = $this->api->post("/v1/turmas/{$id}/professores", $request->only('usuario_id', 'vinculo_id'));

        if ($resp->failed()) {
            return back()->with('erro', $resp->json('message') ?? 'Não foi possível vincular o professor.');
        }

        return back()->with('sucesso', 'Professor vinculado à turma.');
    }

    public function desvincularProfessor(int $id, int $usuarioId)
    {
        $resp = $this->api->delete("/v1/turmas/{$id}/professores/{$usuarioId}", session('auth_vinculos') ? request()->validate(['vinculo_id' => 'required|integer|min:1']) : []);

        if ($resp->failed()) {
            return back()->with('erro', 'Não foi possível desvincular o professor.');
        }

        return back()->with('sucesso', 'Professor desvinculado da turma.');
    }

    public function update(Request $request, int $id)
    {
        if (\App\Support\Acesso::pessoal()) $request->validate(['escola_id'=>'prohibited']);
        $request->validate([
            'nome'  => 'required|string|max:50',
            'serie' => 'required|string|max:50',
            'turno' => 'required|string',
        ]);

        $resp = $this->api->put("/v1/turmas/{$id}", $request->only(
            'escola_id','nome','serie','turno','ano_letivo'
        ));

        if ($resp->failed()) {
            return back()->withInput()
                ->with('erro', $resp->json('message') ?? 'Não foi possível atualizar a turma.');
        }

        return redirect()->route('turmas.show', $id)->with('sucesso', 'Turma atualizada.');
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
