@if(\App\Support\Acesso::pode('aluno.ver'))
@php($pedagogico=!\App\Support\Acesso::administrativo() && \App\Support\Acesso::pode('relatorio.turma') && \App\Support\Acesso::pode('resultado.ver'))
@php($gestorAluno=in_array(\App\Support\Acesso::perfil(),['coordenador','dir_escolar','vice_dir_escolar','secretario_escolar'],true))
<div class="card">
<div class="card-pad">
{{-- Seção de alunos --}}
<h2 class="section-title">
  <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
    <circle cx="9" cy="7" r="4"/>
    <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
    <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
  </svg>
  Alunos da Turma
  <span class="badge" style="margin-left:6px;font-size:13px;">{{ count($turma['alunos'] ?? []) }}</span>
</h2>

<div style="display:flex;gap:12px;align-items:center;margin-bottom:16px;flex-wrap:wrap;">
  <div style="position:relative;flex:1;min-width:200px;">
    <svg style="position:absolute;left:12px;top:50%;transform:translateY(-50%);opacity:.4;" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
    <input id="aluno-search" class="input" type="search" placeholder="Buscar aluno por nome…"
           style="padding-left:36px;" data-turma-filtrar-alunos />
  </div>
  @pode('aluno.gerir')<a href="{{ route('alunos.create', ['turma_id' => $turma['id']]) }}" class="btn btn-primary btn-sm"><x-icone nome="adicionar" /> Adicionar aluno</a>@endpode
</div>

</div>
  <x-tabela :interna="true" :integrada="true" titulo="Alunos da turma" modo="lista"><table class="tabela-padrao table " id="alunos-table"><caption class="sr-only">Alunos da turma</caption>
    <thead>
      <tr>
        <th scope="col">Aluno</th>
        <th scope="col">Matrícula</th>
        @if($pedagogico)<th scope="col">Média geral</th>
        <th scope="col">Provas</th>@endif
        <th scope="col">Status</th>
        <th class="tabela-coluna-acoes" scope="col">Ações</th>
      </tr>
    </thead>
    <tbody id="alunos-tbody">
      @forelse($turma['alunos'] ?? [] as $a)
        <tr data-nome="{{ strtolower($a['nome']) }}">
          <td class="tabela-principal" data-rotulo="Aluno"><div class="tabela-valor">
            <div class="student-name">
              <x-avatar :avatar-id="$a['avatar_id'] ?? null" :nome="$a['nome']" :aluno-id="$a['id']" :tem-foto="$a['tem_foto']??false" :imagem-versao="$a['imagem_versao']??1" tamanho="sm" />
              <strong>{{ $a['nome'] }}</strong>
            </div>
          </div></td>
          <td style="font-family:var(--font-mono);" data-rotulo="Matrícula"><div class="tabela-valor">{{ $a['matricula'] ?? '—' }}</div></td>
          @if($pedagogico)<td style="font-family:var(--font-mono);" data-rotulo="Média geral"><div class="tabela-valor">{{ ($a['media_geral'] ?? null) !== null ? number_format($a['media_geral'], 1, ',', '') : '—' }}</div></td>
          <td style="font-family:var(--font-mono);" data-rotulo="Provas"><div class="tabela-valor">{{ $a['total_provas'] ?? 'Não informado' }}</div></td>@endif
          <td data-rotulo="Status"><div class="tabela-valor">
            @if($a['ativo'] ?? true)
              <span class="badge badge-success badge-dot">Em uso</span>
            @else
              <span class="badge badge-danger badge-dot">Cadastro arquivado</span>
            @endif
          </div></td>
          <td class="tabela-acoes" data-rotulo="Ações"><div class="tabela-valor tu1-acoes"><a href="{{ route('alunos.show', $a['id']) }}" class="btn btn-sm btn-secondary"><x-icone nome="ver" /> Ver</a>
          @if($gestorAluno)
          @pode('aluno.gerir')<a class="btn btn-sm btn-secondary" href="{{ route('alunos.edit',$a['id']) }}"><x-icone nome="editar" /> Editar<span class="sr-only"> {{ $a['nome'] }}</span></a>
          @if($a['ativo'])<a class="btn btn-sm btn-secondary" href="{{ route('alunos.transferencia',$a['id']) }}" data-transferencia-abrir><x-icone nome="transferir" /> Transferir<span class="sr-only"> {{ $a['nome'] }} entre turmas</span></a>@endif @endpode
          @pode('aluno.desativar')<x-arquivamento tipo="alunos" :registro="$a" />@endpode
          @endif
          </div></td>
        </tr>
      @empty
        <tr id="empty-row">
          <td colspan="{{ $pedagogico?6:4 }}" style="text-align:center;padding:48px 20px;color:var(--muted);">
            <div class="icone-grande"><x-icone nome="aluno" /></div>
            <div style="font-size:15px;font-weight:600;color:var(--fg-2);margin-bottom:6px;">Nenhum aluno matriculado</div>
            <p>Os alunos aparecerão aqui após o cadastro no módulo de Alunos.</p>
          </td>
        </tr>
      @endforelse
    </tbody>
  </table></x-tabela>
  <div id="no-results" style="display:none;padding:32px;text-align:center;color:var(--muted);font-size:14px;">
    Nenhum aluno corresponde à busca.
  </div>
</div>


@if($gestorAluno && \App\Support\Acesso::pode('aluno.gerir'))
<dialog class="tu1-dialog" data-transferencia-modal aria-labelledby="transferencia-titulo"><div class="tu1-dialog-cabecalho"><h2 id="transferencia-titulo">Transferir aluno entre turmas</h2><button class="btn btn-ghost tu1-icone-acao" type="button" data-transferencia-fechar aria-label="Fechar transferência" title="Fechar transferência"><x-icone nome="fechar" /></button></div><div data-transferencia-corpo></div></dialog>
@push('scripts')<script src="@ativo('js/turma-transferencia.js')" defer></script>@endpush
@endif

@endif
