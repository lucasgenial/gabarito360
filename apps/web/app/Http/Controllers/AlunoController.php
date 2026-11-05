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
            'erroLista' => !$resp->successful(),
            'haFiltros' => collect(['busca', 'turma_id', 'ativo'])->contains(fn (string $campo) => $request->filled($campo)),
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
        $this->validarMinimo($request, true);
        $request->validate([
            'avatar_id' => 'sometimes|nullable|integer|between:1,36',
            'turma_id'        => 'required|integer',
            'nome'            => 'required|string|max:200',
            'matricula'       => 'required|string|max:20',
            'data_nascimento' => \App\Support\Acesso::pessoal() ? 'prohibited' : 'required|date',
            'cpf'             => 'sometimes|string|nullable',
            'genero'          => 'sometimes|in:M,F,O|nullable',
            'foto'            => 'nullable|file|image|mimes:jpg,jpeg,png,webp|max:2048|dimensions:max_width=4096,max_height=4096',
            'foto_path'       => 'prohibited',
        ]);

        $dados = $request->only(
            'avatar_id','turma_id','nome','matricula','data_nascimento','genero','nome_responsavel'
        );

        if ($cpf = $request->input('cpf')) {
            $dados['cpf'] = preg_replace('/\D/', '', $cpf);
        }

        $resp = $request->hasFile('foto') ? $this->api->postFile('/v1/alunos','foto',$request->file('foto'),$dados) : $this->api->post('/v1/alunos', $dados);

        if ($resp->failed()) {
            return back()->withInput()
                ->with('erro', $resp->json('message') ?? 'Não foi possível cadastrar o aluno.');
        }

        $turmaId = $request->input('turma_id');
        return redirect()->route('turmas.show', $turmaId)
            ->with('sucesso', 'Aluno cadastrado.');
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
        $this->validarMinimo($request);
        $request->validate([
            'avatar_id' => 'sometimes|nullable|integer|between:1,36',
            'foto' => 'prohibited',
            'foto_path' => 'prohibited',
            'nome'      => 'required|string|max:200',
            'matricula' => 'required|string|max:20',
            'cpf'       => 'sometimes|string|nullable',
            'genero'    => 'sometimes|in:M,F,O|nullable',
        ]);

        $dados = $request->only(
            'avatar_id','turma_id','nome','matricula','data_nascimento','genero','nome_responsavel'
        );

        if ($cpf = $request->input('cpf')) {
            $dados['cpf'] = preg_replace('/\D/', '', $cpf);
        }

        $resp = $this->api->put("/v1/alunos/{$id}", $dados);

        if ($resp->failed()) {
            return back()->withInput()
                ->with('erro', $resp->json('message') ?? 'Não foi possível atualizar o aluno.');
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
        return back()->with('sucesso', "Aluno {$status}.");
    }

    public function imagem(Request $r, int $id)
    {
        $r->validateWithBag('imagem',['tipo'=>'required|in:avatar,foto','avatar_id'=>'nullable|integer|between:1,36',
            'foto'=>'required_if:tipo,foto|file|image|mimes:jpg,jpeg,png,webp|max:2048|dimensions:max_width=4096,max_height=4096']);
        try {
            $resp = $r->input('tipo')==='foto' ? $this->api->postFile('/v1/alunos/'.$id.'/imagem','foto',$r->file('foto'),['tipo'=>'foto'])
                : $this->api->post('/v1/alunos/'.$id.'/imagem',$r->only('tipo','avatar_id'));
        } catch (\Illuminate\Http\Client\ConnectionException) {
            return response()->json(['message'=>'Não foi possível confirmar a atualização. Atualize a página para conferir.'],503);
        }
        if($resp->status()===429){
            $segundos=max(1,min(60,(int)$resp->header('Retry-After')?:60));
            return response()->json(['message'=>'Muitas tentativas seguidas. Aguarde alguns instantes e tente novamente.','retry_after'=>$segundos],429)->header('Retry-After',(string)$segundos);
        }
        if (!$resp->successful()) return response()->json(['message'=>$resp->json('message')??'Não foi possível salvar a imagem.'], $resp->status()>=500?503:($resp->status()===404?404:422));
        $d = array_intersect_key($resp->json('data')??[],array_flip(['avatar_id','tem_foto','imagem_versao']));
        $d['foto_url'] = route('alunos.foto',['id'=>$id,'v'=>$d['imagem_versao']??1]);
        return response()->json(['data'=>$d]);
    }
    public function foto(int $id)
    {
        $r = $this->api->getArquivo('/v1/alunos/'.$id.'/foto');
        abort_unless($r->successful(),$r->status()===403?403:404);
        return response($r->body())->header('Content-Type','image/webp')->header('Cache-Control','private, no-store')->header('X-Content-Type-Options','nosniff');
    }

    private function validarMinimo(Request $request, bool $aceitarRetrato = false): void
    {
        if (\App\Support\Acesso::pessoal()) $request->validate(array_fill_keys(
            array_diff(['cpf', 'data_nascimento', 'genero', 'nome_responsavel', 'foto', 'foto_path'], $aceitarRetrato ? ['foto'] : []), 'prohibited'));
    }
}
