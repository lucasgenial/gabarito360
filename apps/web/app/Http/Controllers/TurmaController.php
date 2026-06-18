<?php

namespace App\Http\Controllers;

use App\Services\ApiClient;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TurmaController extends Controller
{
    public function __construct(private ApiClient $api) {}

    public function index(Request $request): View
    {
        $resp = $this->api->get('/v1/turmas', $request->only('busca', 'escola_id', 'ano_letivo', 'ativo'));

        $turmas  = $resp->successful() ? $resp->json('data.turmas') : [];
        $kpis    = $resp->successful() ? $resp->json('data.kpis')   : [];

        $escolas = $this->api->get('/v1/escolas')->json('data.escolas') ?? [];

        return view('turmas.index', [
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
            'escola_id'  => 'required|integer',
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
                ->with('erro', $resp->json('message') ?? 'Erro ao criar turma.');
        }

        return redirect()->route('turmas.index')->with('sucesso', 'Turma criada com sucesso.');
    }

    public function show(int $id): View
    {
        $resp = $this->api->get("/v1/turmas/{$id}");

        if ($resp->failed()) {
            abort(404, 'Turma não encontrada.');
        }

        $escolas     = $this->api->get('/v1/escolas')->json('data.escolas') ?? [];
        $professores = $this->api->get("/v1/turmas/{$id}/professores")->json('data.professores') ?? [];
        $todosProfessores = $this->api->get('/v1/usuarios', ['perfil' => 'professor'])->json('data.usuarios') ?? [];

        return view('turmas.show', [
            'turma'            => $resp->json('data'),
            'escolas'          => $escolas,
            'professores'      => $professores,
            'todosProfessores' => $todosProfessores,
        ]);
    }

    public function vincularProfessor(Request $request, int $id)
    {
        $resp = $this->api->post("/v1/turmas/{$id}/professores", $request->only('usuario_id'));

        if ($resp->failed()) {
            return back()->with('erro', $resp->json('message') ?? 'Erro ao vincular professor.');
        }

        return back()->with('sucesso', 'Professor vinculado à turma.');
    }

    public function desvincularProfessor(int $id, int $usuarioId)
    {
        $resp = $this->api->delete("/v1/turmas/{$id}/professores/{$usuarioId}");

        if ($resp->failed()) {
            return back()->with('erro', 'Erro ao desvincular professor.');
        }

        return back()->with('sucesso', 'Professor desvinculado da turma.');
    }

    public function update(Request $request, int $id)
    {
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
                ->with('erro', $resp->json('message') ?? 'Erro ao atualizar turma.');
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
        return back()->with('sucesso', "Turma {$status} com sucesso.");
    }
}
