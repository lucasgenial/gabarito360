<?php

namespace App\Http\Controllers;

use App\Services\ApiClient;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MembroController extends Controller
{
    private const PERFIS = [
        'admin_rede'  => ['label' => 'Administrador da Rede', 'badge' => 'badge-info',    'ini' => 'A'],
        'dir_nucleo'  => ['label' => 'Diretor de Núcleo',     'badge' => 'badge-warn',    'ini' => 'N'],
        'dir_escolar' => ['label' => 'Diretor Escolar',       'badge' => 'badge-purple',  'ini' => 'D'],
        'coordenador' => ['label' => 'Coordenador Pedagógico','badge' => 'badge-amber',   'ini' => 'C'],
        'professor'   => ['label' => 'Professor',             'badge' => 'badge-info',    'ini' => 'P'],
        'aplicador'   => ['label' => 'Aplicador',             'badge' => 'badge-muted',   'ini' => 'AP'],
        'secretario_educacao' => ['label' => 'Secretário de Educação', 'badge' => 'badge-info', 'ini' => 'S'],
        'aluno'       => ['label' => 'Aluno',                 'badge' => 'badge-success', 'ini' => 'AL'],
    ];

    /** Só os perfis que o usuário logado pode cadastrar/atribuir (a API confere de novo). */
    private function perfisPermitidos(): array
    {
        return array_intersect_key(self::PERFIS, array_flip(\App\Support\Acesso::perfisGeridos()));
    }

    private function alvo(): array
    {
        if (!session('auth_vinculos')) return [];
        return request()->validate(['vinculo_id' => 'required|integer|min:1']);
    }

    public function __construct(private ApiClient $api) {}

    public function index(Request $request): View
    {
        $resp = $this->api->get('/v1/usuarios', $request->only('perfil', 'busca'));

        $usuarios = $resp->successful() ? $resp->json('data.usuarios') : [];
        $totais   = $resp->successful() ? $resp->json('data.totais')   : [];

        $agrupados = collect($usuarios)->groupBy('perfil');

        return view('membros.index', [
            'agrupados'  => $agrupados,
            'totais'     => $totais,
            'perfis'     => self::PERFIS,
            'filtroPerf' => $request->query('perfil', ''),
            'filtroBusc' => $request->query('busca', ''),
        ]);
    }

    public function create(): View
    {
        $escolas = $this->api->get('/v1/escolas')->json('data.escolas') ?? [];
        $nucleos = \App\Support\Acesso::perfil() === 'admin_rede' ? ($this->api->get('/v1/nucleos')->json('data.nucleos') ?? []) : [];
        $turmas  = \App\Support\Acesso::pode('turma.vincular_professor')
            ? collect($this->api->get('/v1/turmas')->json('data.turmas') ?? [])->where('ativo', true)
                ->map(fn ($t) => ['id' => $t['id'], 'nome' => $t['nome'], 'escola_id' => $t['escola_id'] ?? null])->sortBy('nome')->values()->all()
            : [];

        return view('membros.create', ['perfis' => $this->perfisPermitidos(), 'escolas' => $escolas, 'nucleos' => $nucleos, 'turmas' => $turmas]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'nome'                  => 'required|string|max:200',
            'email'                 => 'required|email',
            'cpf'                   => 'required|string',
            'perfil'                => 'required|string',
            'data_nascimento'       => 'sometimes|date|nullable',
            'data_ingresso'         => 'sometimes|date|nullable',
        ]);

        $dados = [
            'nome'                   => $request->nome,
            'email'                  => $request->email,
            'cpf'                    => preg_replace('/\D/', '', $request->cpf),
            'perfil'                 => $request->perfil,
            'ativo'                  => $request->boolean('ativo', true),
            'data_nascimento'        => $request->input('data_nascimento'),
            'telefone'               => $request->input('telefone'),
            'data_ingresso'          => $request->input('data_ingresso'),
            'formacao_academica'     => $request->input('formacao_academica'),
            'especializacao'         => $request->input('especializacao'),
            'registro_profissional'  => $request->input('registro_profissional'),
            'observacoes'            => $request->input('observacoes'),
            'escola_id'              => $request->input('escola_id'),
            'nucleo_id'              => $request->input('nucleo_id'),
            'turma_ids'              => $request->perfil === 'professor' ? array_map('intval', (array) $request->input('turma_ids', [])) : [],
        ];

        $resp = $this->api->post('/v1/usuarios', $dados);

        if ($resp->failed()) {
            return back()->withInput()
                ->with('erro', $resp->json('message') ?? 'Não foi possível cadastrar o membro.');
        }

        // Passo 4: permissões do novo membro (padrão do perfil + extras)
        return redirect()->route('membros.permissoes', array_filter(['id' => $resp->json('data.id'), 'vinculo_id' => $resp->json('data.vinculo_id')]))
            ->with('sucesso', 'Membro cadastrado. Confira as permissões padrão do perfil e conceda extras, se precisar.');
    }

    public function edit(int $id): View
    {
        $resp = $this->api->get("/v1/usuarios/{$id}", $this->alvo());

        if ($resp->failed()) {
            abort(404, 'Membro não encontrado.');
        }

        if (session('auth_vinculos')) return view('membros.vinculo', ['membro' => $resp->json('data')]);
        $escolas = $this->api->get('/v1/escolas')->json('data.escolas') ?? [];

        return view('membros.edit', [
            'membro'  => $resp->json('data'),
            'perfis'  => $this->perfisPermitidos(),
            'escolas' => $escolas,
        ]);
    }

    public function update(Request $request, int $id)
    {
        if (session('auth_vinculos')) {
            $dados = $this->alvo() + $request->validate(['ativo' => 'required|boolean']);
            $resp = $this->api->put("/v1/usuarios/{$id}", $dados);
            return back()->with($resp->successful() ? 'sucesso' : 'erro', $resp->successful() ? 'Acesso deste vínculo atualizado.' : ($resp->json('message') ?? 'Não foi possível atualizar o vínculo.'));
        }
        $request->validate([
            'nome'       => 'required|string|max:200',
            'email'      => 'required|email',
            'perfil'     => 'required|string',
        ]);

        $dados = [
            'nome'                   => $request->nome,
            'email'                  => $request->email,
            'perfil'                 => $request->perfil,
            'ativo'                  => $request->boolean('ativo', true),
            'data_nascimento'        => $request->input('data_nascimento'),
            'telefone'               => $request->input('telefone'),
            'data_ingresso'          => $request->input('data_ingresso'),
            'formacao_academica'     => $request->input('formacao_academica'),
            'especializacao'         => $request->input('especializacao'),
            'registro_profissional'  => $request->input('registro_profissional'),
            'observacoes'            => $request->input('observacoes'),
            'escola_id'              => $request->input('escola_id'),
            'forcar_troca_senha'     => $request->boolean('forcar_troca_senha'),
        ];

        $resp = $this->api->put("/v1/usuarios/{$id}", $dados);

        if ($resp->failed()) {
            return back()->withInput()
                ->with('erro', $resp->json('message') ?? 'Não foi possível atualizar o membro.');
        }

        return redirect()->route('membros.index')
            ->with('sucesso', 'Membro atualizado.');
    }

    public function toggle(int $id)
    {
        $resp = $this->api->post("/v1/usuarios/{$id}/toggle", $this->alvo());

        if ($resp->failed()) {
            return back()->with('erro', 'Não foi possível alterar o status do membro.');
        }

        $status = $resp->json('data.ativo') ? 'ativado' : 'desativado';

        return back()->with('sucesso', "Membro {$status}.");
    }

    /** Permissões do membro: padrão do perfil (com retirada), extras e histórico. */
    public function permissoes(int $id): View
    {
        $resp = $this->api->get("/v1/usuarios/{$id}/permissoes", $this->alvo());
        if ($resp->failed()) {
            abort(404, 'Membro não encontrado.');
        }

        return view('membros.permissoes', ['dados' => $resp->json('data'), 'perfis' => self::PERFIS]);
    }

    public function salvarPermissoes(Request $request, int $id)
    {
        $atual = $this->api->get("/v1/usuarios/{$id}/permissoes", $this->alvo())->json('data.itens') ?? [];
        $itens = [];
        foreach ($atual as $p) {
            if (empty($p['pode_alterar'])) {
                continue;
            }
            $chave = $p['chave'];
            if ($p['tipo'] === 'padrao') {
                $itens[] = ['permissao' => $chave, 'estado' => filter_var($request->input('retirar', [])[$chave] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'retirar' : 'padrao'];
            } else {
                $conceder = filter_var($request->input('conceder', [])[$chave] ?? false, FILTER_VALIDATE_BOOLEAN);
                $itens[] = ['permissao' => $chave, 'estado' => $conceder ? 'conceder' : 'padrao',
                            'valida_ate' => $conceder ? (($request->input('valida_ate', [])[$chave] ?? null) ?: null) : null];
            }
        }
        if (!$itens) {
            return back()->with('erro', 'Você não pode alterar as permissões deste membro.');
        }

        $resp = $this->api->put("/v1/usuarios/{$id}/permissoes", $this->alvo() + ['itens' => $itens]);
        if ($resp->failed()) {
            $detalhes = collect($resp->json('errors') ?? [])->flatten()->implode(' ');

            return back()->with('erro', trim(($resp->json('message') ?? 'Não foi possível salvar.') . ' ' . $detalhes));
        }

        return back()->with('sucesso', 'Permissões atualizadas.');
    }

    /** "O que cada perfil pode" — leitura do catálogo (docs/03 v2). */
    public function perfis(): View
    {
        $resp = $this->api->get('/v1/permissoes/perfis');

        return view('membros.perfis', ['catalogo' => $resp->json('data') ?? [], 'perfis' => self::PERFIS]);
    }

    public function convite(int $id)
    {
        $resp = $this->api->post("/v1/usuarios/{$id}/convite", $this->alvo());

        if ($resp->failed()) {
            return back()->with('erro', $resp->json('message') ?? 'Não foi possível enviar o convite.');
        }

        return back()->with('sucesso', 'Convite enviado.');
    }

    public function linkSenha(int $id)
    {
        abort_if(session('auth_vinculos'), 403);
        $resp = $this->api->post("/v1/usuarios/{$id}/link-senha");

        if ($resp->failed()) {
            return back()->with('erro', $resp->json('message') ?? 'Não foi possível enviar o link.');
        }

        return back()->with('sucesso', 'Link para criar nova senha enviado por e-mail.');
    }
}
