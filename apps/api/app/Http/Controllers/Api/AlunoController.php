<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\Escopo;
use App\Support\Espacos;
use App\Support\EspacoPessoal;
use App\Http\Responses\ApiResponse;
use App\Models\Aluno;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AlunoController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query    = Escopo::alunos($request->user());

        if ($turmaId = $request->query('turma_id')) {
            $query->where('turma_id', $turmaId);
        }

        if ($busca = $request->query('busca')) {
            $query->where(function ($q) use ($busca) {
                $q->where('nome', 'like', "%{$busca}%")
                  ->orWhere('matricula', 'like', "%{$busca}%");
            });
        }

        if ($request->has('ativo')) {
            $query->where('ativo', $request->boolean('ativo'));
        }

        $lista = $query->with('turma:id,nome,serie,escola_id')->orderBy('nome')->get();
        $versoes=app(\App\Services\ArquivamentoCadastro::class)->versoes('aluno',$lista->modelKeys());
        $alunos = $lista->map(fn (Aluno $a) => array_merge($a->toArray(), [
                'arquivamento'=>app(\App\Services\ArquivamentoCadastro::class)->estado($a,$versoes[$a->id]??0),
                'turma_nome' => $a->turma?->nome,
                'turma_serie'=> $a->turma?->serie,
            ]));

        $baseQ = Escopo::alunos($request->user());

        return ApiResponse::success([
            'alunos' => $alunos,
            'kpis'   => [
                'total_alunos' => $baseQ->count(),
                'total_ativos' => (clone $baseQ)->where('ativo', true)->count(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $pessoal = EspacoPessoal::validarAluno($request, true);
        if ($request->has('ativo') && !$request->boolean('ativo')) abort_unless(\App\Support\Permissao::pode($request->user(), 'aluno.desativar'), 403);
        $request->validate(['turma_id' => 'required|integer']);
        $turma = Escopo::turmas($request->user())->findOrFail((int) $request->input('turma_id'));
        $espaco = Espacos::ativo() ? (int) $turma->espaco_id : null;
        $data = $request->validate([
            'avatar_id' => 'sometimes|nullable|integer|between:1,36',
            'turma_id'        => 'required|integer|exists:turmas,id',
            'nome'            => 'required|string|max:200',
            'matricula'       => ['required', 'string', 'max:20', Rule::unique('alunos', 'matricula')->when($espaco !== null, fn ($q) => $q->where('espaco_id', $espaco))],
            'cpf'             => ['sometimes', 'string', 'size:11', 'nullable', new \App\Rules\UnicoProtegido('alunos', 'cpf', espaco: $espaco)],
            'data_nascimento' => $pessoal ? 'prohibited' : 'required|date',
            'genero'          => 'sometimes|in:M,F,O|nullable',
            'nome_responsavel'=> 'sometimes|string|max:200|nullable',
            'ativo'           => 'sometimes|boolean',
            'foto'            => 'nullable|'.\App\Services\ImagemPerfil::REGRA,
            'foto_path'       => 'prohibited',
        ]);

        $foto = $request->hasFile('foto') ? app(\App\Services\ImagemPerfil::class)->guardar($request->file('foto'), 'alunos/fotos') : null;
        unset($data['foto']);
        try {
            $aluno = app(\App\Services\ArquivamentoCadastro::class)->criar($request->user(),'aluno',$data,function($data)use($pessoal,$turma,$request,$foto){
                Escopo::turmas($request->user())->where('ativo',true)->findOrFail($turma->id);
                $aluno = new Aluno($pessoal ? EspacoPessoal::dadosMinimos($data) : $data);
                if (Espacos::ativo()) $aluno->forceFill(Espacos::autoria((int) $turma->espaco_id, $request->user()));
                if ($pessoal) $aluno->forceFill(['espaco_tipo'=>'pessoal']);
                if ($foto) $aluno->forceFill(['foto_path'=>$foto,'avatar_id'=>null]);
                $aluno->save();
                return $aluno;
            });
        } catch (\Throwable $e) { if ($foto) \Illuminate\Support\Facades\Storage::disk('local')->delete($foto); throw $e; }

        return ApiResponse::success(array_merge($aluno->load('turma:id,nome,serie')->toArray(),['arquivamento'=>app(\App\Services\ArquivamentoCadastro::class)->estado($aluno)]),null,201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $query    = Escopo::alunos($request->user())->with('turma:id,nome,serie,escola_id');

        $aluno = $query->find($id);

        if (!$aluno) {
            return ApiResponse::notFound('Aluno não encontrado.');
        }

        return ApiResponse::success(array_merge($aluno->toArray(), [
            'arquivamento'=>app(\App\Services\ArquivamentoCadastro::class)->estado($aluno),
            'turma_nome'  => $aluno->turma?->nome,
            'turma_serie' => $aluno->turma?->serie,
        ]));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $pessoal = EspacoPessoal::validarAluno($request);
        $aluno = Escopo::alunos($request->user())->find($id);

        if (!$aluno) {
            return ApiResponse::notFound('Aluno não encontrado.');
        }

        if ($request->has('ativo') && $request->boolean('ativo') !== (bool) $aluno->ativo) abort_unless(\App\Support\Permissao::pode($request->user(), 'aluno.desativar'), 403);
        $data = $request->validate([
            'avatar_id' => 'sometimes|nullable|integer|between:1,36',
            'turma_id'        => 'sometimes|integer|exists:turmas,id',
            'nome'            => 'sometimes|string|max:200',
            'matricula'       => ['sometimes', 'string', 'max:20', Rule::unique('alunos', 'matricula')->ignore($aluno->id)->when(Espacos::ativo(), fn ($q) => $q->where('espaco_id', $aluno->espaco_id))],
            'cpf'             => ['sometimes', 'string', 'size:11', 'nullable', new \App\Rules\UnicoProtegido('alunos', 'cpf', $id, Espacos::ativo() ? (int) $aluno->espaco_id : null)],
            'data_nascimento' => 'sometimes|date|nullable',
            'genero'          => 'sometimes|in:M,F,O|nullable',
            'nome_responsavel'=> 'sometimes|string|max:200|nullable',
            'ativo'           => 'sometimes|boolean',
            'foto'            => 'prohibited',
            'foto_path'       => 'prohibited',
        ]);

        if (isset($data['turma_id'])) {
            $destino = Escopo::turmas($request->user())->findOrFail((int) $data['turma_id']);
            $origem = Escopo::turmas($request->user())->findOrFail($aluno->turma_id);
            abort_unless((int) $destino->escola_id === (int) $origem->escola_id, 422, 'Transferência entre escolas exige procedimento próprio.');
            if (Espacos::ativo()) abort_unless((int) $destino->espaco_id === (int) $aluno->espaco_id, 422);
        }
        $aluno=app(\App\Services\ArquivamentoCadastro::class)->atualizar($request->user(),'aluno',$id,$data,function($registro,$dados)use($pessoal,$request,$id){
            if($pessoal)app(\App\Services\AtualizarAlunoPessoal::class)->executar($request->user(),$id,EspacoPessoal::dadosMinimos($dados));
            else app(\App\Services\MovimentarAlunoTurma::class)->executar($request->user(),$id,$dados);
        });
        return ApiResponse::success(array_merge($aluno->toArray(),['arquivamento'=>app(\App\Services\ArquivamentoCadastro::class)->estado($aluno)]));
    }

    public function toggle(Request $request, int $id): JsonResponse
    {
        $d=$request->validate(['esperado_ativo'=>'sometimes|boolean','versao'=>'sometimes|integer|min:0','chave'=>'sometimes|uuid']);
        $estado=app(\App\Services\ArquivamentoCadastro::class)->toggleLegado($request->user(),'aluno',$id,$d);
        return ApiResponse::success($estado,$estado['ativo']?'Aluno ativado.':'Aluno desativado.');
    }

    public function transferir(Request $request,int $id): JsonResponse
    {
        abort_unless(\App\Services\MovimentarAlunoTurma::autorizado($request->user()),403);
        $d=$request->validate(['turma_id'=>'required|integer|min:1','turma_origem_id'=>'required|integer|min:1','chave'=>'required|uuid','motivo'=>'required|string|min:5|max:500']);
        app(\App\Services\MovimentarAlunoTurma::class)->executar($request->user(),$id,['turma_id'=>$d['turma_id']],$d);
        return ApiResponse::success(['transferido'=>true],'Aluno transferido. Histórico preservado.');
    }
}
