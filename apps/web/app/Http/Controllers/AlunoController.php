<?php

namespace App\Http\Controllers;

use App\Services\ApiClient;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AlunoController extends Controller
{
    public function __construct(private ApiClient $api) {}

    public function index(Request $request): View
    {
        $resp = $this->api->get('/v1/alunos', $request->only('busca', 'turma_id', 'ativo'));

        $alunos = $resp->successful() ? $resp->json('data.alunos') : [];
        $kpis   = $resp->successful() ? $resp->json('data.kpis')   : [];

        $turmas = $this->api->get('/v1/turmas')->json('data.turmas') ?? [];

        return view('alunos.index', [
            'alunos'   => $alunos,
            'kpis'     => $kpis,
            'turmas'   => $turmas,
            'busca'    => $request->query('busca', ''),
            'turma_id' => $request->query('turma_id', ''),
        ]);
    }

    public function create(Request $request): View
    {
        $turmas = $this->api->get('/v1/turmas', ['ativo' => 1])->json('data.turmas') ?? [];

        return view('alunos.create', [
            'turmas'    => $turmas,
            'turma_id'  => $request->query('turma_id', ''),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'turma_id'        => 'required|integer',
            'nome'            => 'required|string|max:200',
            'matricula'       => 'required|string|max:20',
            'data_nascimento' => 'required|date',
            'cpf'             => 'sometimes|string|nullable',
            'genero'          => 'sometimes|in:M,F,O|nullable',
            'foto'            => 'sometimes|image|max:4096|nullable',
        ]);

        $dados = $request->only(
            'turma_id','nome','matricula','data_nascimento','genero','nome_responsavel'
        );

        if ($cpf = $request->input('cpf')) {
            $dados['cpf'] = preg_replace('/\D/', '', $cpf);
        }

        if ($request->hasFile('foto')) {
            $resp = $this->api->postFile('/v1/alunos', 'foto', $request->file('foto'), $dados);
        } else {
            $resp = $this->api->post('/v1/alunos', $dados);
        }

        if ($resp->failed()) {
            return back()->withInput()
                ->with('erro', $resp->json('message') ?? 'Erro ao cadastrar aluno.');
        }

        $turmaId = $request->input('turma_id');
        return redirect()->route('turmas.show', $turmaId)
            ->with('sucesso', 'Aluno cadastrado com sucesso.');
    }

    public function show(int $id): View
    {
        $resp = $this->api->get("/v1/alunos/{$id}");

        if ($resp->failed()) {
            abort(404, 'Aluno não encontrado.');
        }

        $turmas = $this->api->get('/v1/turmas')->json('data.turmas') ?? [];

        return view('alunos.show', [
            'aluno'  => $resp->json('data'),
            'turmas' => $turmas,
        ]);
    }

    public function update(Request $request, int $id)
    {
        $request->validate([
            'nome'      => 'required|string|max:200',
            'matricula' => 'required|string|max:20',
            'cpf'       => 'sometimes|string|nullable',
            'genero'    => 'sometimes|in:M,F,O|nullable',
        ]);

        $dados = $request->only(
            'turma_id','nome','matricula','data_nascimento','genero','nome_responsavel'
        );

        if ($cpf = $request->input('cpf')) {
            $dados['cpf'] = preg_replace('/\D/', '', $cpf);
        }

        $resp = $this->api->put("/v1/alunos/{$id}", $dados);

        if ($resp->failed()) {
            return back()->withInput()
                ->with('erro', $resp->json('message') ?? 'Erro ao atualizar aluno.');
        }

        return redirect()->route('alunos.show', $id)->with('sucesso', 'Aluno atualizado.');
    }

    public function toggle(int $id)
    {
        $resp = $this->api->post("/v1/alunos/{$id}/toggle");

        if ($resp->failed()) {
            return back()->with('erro', 'Não foi possível alterar o status do aluno.');
        }

        $status = $resp->json('data.ativo') ? 'ativado' : 'desativado';
        return back()->with('sucesso', "Aluno {$status} com sucesso.");
    }
}
