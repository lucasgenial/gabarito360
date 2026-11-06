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
        return redirect()->route('turmas.show', ['id'=>$turmaId, 'secao'=>'alunos'])
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
        try { $request->validate([
            'avatar_id' => 'sometimes|nullable|integer|between:1,36',
            'foto' => 'prohibited',
            'foto_path' => 'prohibited',
            'nome'      => 'required|string|max:200',
            'matricula' => 'required|string|max:20',
            'cpf'       => 'sometimes|string|nullable',
            'genero'    => 'sometimes|in:M,F,O|nullable',
        ]); } catch (\Illuminate\Validation\ValidationException $e) { if($request->boolean('voltar_turma'))$e->redirectTo(route('alunos.edit',$id)); throw $e; }

        $dados = $request->only(
            'avatar_id','turma_id','nome','matricula','data_nascimento','genero','nome_responsavel'
        );

        if ($cpf = $request->input('cpf')) {
            $dados['cpf'] = preg_replace('/\D/', '', $cpf);
        }

        $resp = $this->api->put("/v1/alunos/{$id}", $dados);

        if ($resp->failed()) {
            return ($request->boolean('voltar_turma')?redirect()->route('alunos.edit',$id):back())->withInput()->withErrors($resp->json('errors')??[])
                ->with('erro', $resp->json('message') ?? 'Não foi possível atualizar o aluno.');
        }

        if($request->boolean('voltar_turma')) {
            $atual=$this->api->get("/v1/alunos/$id");
            if($atual->successful()) return redirect()->route('turmas.show',['id'=>$atual->json('data.turma_id'),'secao'=>'alunos'])->with('sucesso','Aluno atualizado.');
        }
        return redirect()->route('alunos.show', $id)->with('sucesso', 'Aluno atualizado.');
    }

    public function toggle(int $id)
    {
        $d=request()->validate(['esperado_ativo'=>'sometimes|boolean']);
        $resp = $this->api->post("/v1/alunos/{$id}/toggle",$d);
        $origem=request()->attributes->get('ur4_retorno');
        $retorno=null;
        if(($origem['nome']??null)==='turmas.show'){
            $a=$this->api->get("/v1/alunos/$id");
            if($a->successful()&&(int)$a->json('data.turma_id')===(int)($origem['parametros']['id']??0))$retorno=redirect()->route('turmas.show',['id'=>$a->json('data.turma_id'),'secao'=>'alunos']);
        }

        if ($resp->failed()) {
            return ($retorno??back())->with('erro', $resp->json('message')??'Não foi possível alterar o status do aluno.');
        }

        $status = $resp->json('data.ativo') ? 'ativado' : 'desativado';
        return ($retorno??back())->with('sucesso', "Aluno {$status}.");
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

    public function edit(int $id): View
    {
        abort_unless(\App\Support\Acesso::pode('aluno.gerir'),403);
        $r=$this->api->get("/v1/alunos/$id"); abort_unless($r->successful(),$r->serverError()?503:404);
        return view('alunos.edit',['aluno'=>$r->json('data')]);
    }

    private function autorizarTransferencia(): void
    {
        abort_unless(in_array(\App\Support\Acesso::perfil(),['coordenador','dir_escolar','vice_dir_escolar','secretario_escolar'],true)&&\App\Support\Acesso::pode('aluno.gerir'),403);
    }

    public function transferencia(int $id): View
    {
        $this->autorizarTransferencia();
        $a=$this->api->get("/v1/alunos/$id"); abort_unless($a->successful(),$a->serverError()?503:404);
        $aluno=$a->json('data');
        $t=$this->api->get('/v1/turmas',['ativo'=>1,'escola_id'=>$aluno['turma']['escola_id']??0]);
        abort_unless($t->successful(),503,'Não foi possível consultar as turmas.');
        $turmas=collect($t->json('data.turmas')??[])->filter(fn($v)=>(int)$v['escola_id']===(int)($aluno['turma']['escola_id']??0)&&(int)$v['id']!==(int)$aluno['turma_id'])->values()->all();
        return view(request()->header('X-G360-Modal')==='transferencia' ? 'alunos._transferencia-form' : 'alunos.transferencia',compact('aluno','turmas'));
    }

    public function transferir(Request $r,int $id)
    {
        $this->autorizarTransferencia();
        try{$d=$r->validate(['turma_id'=>'required|integer|min:1','turma_origem_id'=>'required|integer|min:1','chave'=>'required|uuid','motivo'=>'required|string|min:5|max:500']);}
        catch(\Illuminate\Validation\ValidationException $e){if($r->expectsJson())return response()->json(['message'=>'Confira os campos informados.','errors'=>$e->errors()],422);$e->redirectTo(route('alunos.transferencia',$id));throw $e;}
        try{$resp=$this->api->post("/v1/alunos/$id/transferencias-turma",$d);}
        catch(\Illuminate\Http\Client\ConnectionException){if($r->expectsJson())return response()->json(['message'=>'Não foi possível confirmar. Confira a turma atual antes de reenviar.','resultado_incerto'=>true],503);return redirect()->route('alunos.transferencia',$id)->withInput()->with('erro','Não foi possível confirmar a transferência. Confira a turma atual antes de tentar novamente.');}
        abort_if(in_array($resp->status(),[403,404],true),$resp->status());
        if($r->expectsJson()){
            if($resp->failed())return response()->json(['message'=>$resp->json('message')??'Não foi possível transferir.','errors'=>$resp->json('errors')??[]],$resp->status());
            session()->flash('sucesso','Aluno transferido. O histórico anterior foi preservado.');
            $destino=\App\Support\NavegacaoWeb::individualizar(\App\Support\NavegacaoWeb::destino('turmas.show',['id'=>$d['turma_id'],'secao'=>'alunos']));
            \App\Support\NavegacaoWeb::guardarMensagens($destino['referencia']);
            return response()->json(['destino'=>$destino]);
        }
        if($resp->failed())return redirect()->route('alunos.transferencia',$id)->withInput()->withErrors($resp->json('errors')??[])->with('erro',$resp->json('message')??'Não foi possível transferir.');
        return redirect()->route('turmas.show',['id'=>$d['turma_id'],'secao'=>'alunos'])->with('sucesso','Aluno transferido. Os resultados anteriores permanecem na turma de aplicação.');
    }
}
