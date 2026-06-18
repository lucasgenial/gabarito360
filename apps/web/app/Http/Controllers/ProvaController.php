<?php

namespace App\Http\Controllers;

use App\Services\ApiClient;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProvaController extends Controller
{
    public function __construct(private ApiClient $api) {}

    public function index(Request $request): View
    {
        $resp = $this->api->get('/v1/provas', $request->only('busca', 'disciplina', 'status'));

        $provas = $resp->successful() ? $resp->json('data.provas') : [];
        $kpis   = $resp->successful() ? $resp->json('data.kpis')   : [];

        return view('provas.index', [
            'provas'     => $provas,
            'kpis'       => $kpis,
            'busca'      => $request->query('busca', ''),
            'disciplina' => $request->query('disciplina', ''),
            'status'     => $request->query('status', ''),
        ]);
    }

    public function create(Request $request): View
    {
        $turmas  = $this->api->get('/v1/turmas', ['ativo' => 1])->json('data.turmas') ?? [];
        $escolas = $this->api->get('/v1/escolas')->json('data.escolas') ?? [];

        return view('provas.create', [
            'turmas'  => $turmas,
            'escolas' => $escolas,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'escola_id'       => 'required|integer',
            'titulo'          => 'required|string|max:255',
            'disciplina'      => 'required|string|max:100',
            'serie'           => 'required|string|max:50',
            'num_questoes'    => 'required|integer|min:1|max:100',
            'num_alternativas'=> 'required|integer|in:3,4,5',
        ]);

        $payload = $request->only(
            'escola_id','titulo','disciplina','serie','num_questoes',
            'num_alternativas','nota_maxima','tipo_pontuacao',
            'anular_se_todas','gerar_cartao_pdf','data_aplicacao'
        );

        if ($request->has('turma_ids')) {
            $payload['turma_ids'] = (array) $request->input('turma_ids');
        }

        $resp = $this->api->post('/v1/provas', $payload);

        if ($resp->failed()) {
            return back()->withInput()
                ->with('erro', $resp->json('message') ?? 'Erro ao criar prova.');
        }

        $id = $resp->json('data.id');
        return redirect()->route('provas.show', $id)
            ->with('sucesso', 'Prova criada como rascunho. Adicione o gabarito antes de publicar.');
    }

    public function show(int $id): View
    {
        $resp = $this->api->get("/v1/provas/{$id}");

        if ($resp->failed()) {
            abort(404, 'Prova não encontrada.');
        }

        $turmas = $this->api->get('/v1/turmas')->json('data.turmas') ?? [];

        return view('provas.show', [
            'prova'  => $resp->json('data'),
            'turmas' => $turmas,
        ]);
    }

    public function update(Request $request, int $id)
    {
        $payload = $request->only(
            'titulo','disciplina','serie','num_questoes','num_alternativas',
            'nota_maxima','tipo_pontuacao','anular_se_todas','gerar_cartao_pdf','data_aplicacao'
        );

        if ($request->has('turma_ids')) {
            $payload['turma_ids'] = (array) $request->input('turma_ids');
        }

        $resp = $this->api->put("/v1/provas/{$id}", $payload);

        if ($resp->failed()) {
            return back()->withInput()
                ->with('erro', $resp->json('message') ?? 'Erro ao atualizar prova.');
        }

        return redirect()->route('provas.show', $id)->with('sucesso', 'Prova atualizada.');
    }

    public function publicar(int $id)
    {
        $resp = $this->api->post("/v1/provas/{$id}/publicar");

        if ($resp->failed()) {
            return back()->with('erro', $resp->json('message') ?? 'Não foi possível publicar a prova.');
        }

        return back()->with('sucesso', 'Prova publicada com sucesso.');
    }

    public function destroy(int $id)
    {
        $resp = $this->api->delete("/v1/provas/{$id}");

        if ($resp->failed()) {
            return back()->with('erro', $resp->json('message') ?? 'Não foi possível excluir a prova.');
        }

        return redirect()->route('provas.index')->with('sucesso', 'Prova excluída.');
    }
}
