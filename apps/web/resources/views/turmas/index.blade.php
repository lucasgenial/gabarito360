@extends('layouts.app')

@section('tela', 'tela-turmas')

@section('title', 'Turmas')

@section('breadcrumb')
<a href="{{ route('painel') }}">Início</a><span class="sep">/</span><span>Turmas</span>
@endsection

@section('content')

<x-pagina titulo="Turmas">
  <x-slot:sub>@if($erroLista)Listagem temporariamente indisponível
  @else
  {{ $haFiltros?'Turmas no recorte selecionado':'Turmas do espaço atual' }}
  @endif
  </x-slot:sub>
    @pode('aluno.importar')<span class="field-help">Importação por planilha em breve.</span>@endpode
    @pode('turma.gerir')<button class="btn btn-primary" data-turma-abrir="modal-criar">+ Nova turma</button>@endpode
</x-pagina>

{{-- Filtros --}}
<form method="GET" class="filtros-cel tabela-filtros-principal" action="{{ route('turmas.index') }}" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;">
  <div style="position:relative;flex:1;min-width:200px;">
    <svg style="position:absolute;left:12px;top:50%;transform:translateY(-50%);opacity:.4;" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
    <input class="input" type="search" name="busca" aria-label="Buscar turma por nome" placeholder="Buscar turma por nome…"
           value="{{ $busca }}" style="padding-left:36px;" />
  </div>
@unless(\App\Support\Acesso::pessoal())
  <select class="select" name="escola_id" aria-label="Filtrar por escola" style="min-width:180px;">
    <option value="">Todas as escolas</option>
    @foreach($escolas as $e)
      <option value="{{ $e['id'] }}" {{ $escola_id == $e['id'] ? 'selected' : '' }}>{{ $e['nome'] }}</option>
    @endforeach
  </select>
@endunless
  <input class="input" type="number" name="ano_letivo" aria-label="Filtrar por ano letivo" placeholder="Ano letivo"
         value="{{ $ano_letivo }}" style="width:130px;" min="2020" max="2099" />
  <button type="submit" class="btn btn-secondary">Filtrar</button>
  <a href="#relacao-turmas" class="btn btn-ghost">Ir para turmas</a>
  @if($haFiltros)
    <a href="{{ route('turmas.index') }}" class="btn btn-ghost">Limpar</a>
  @endif
</form>
@include('turmas._organizar_painel',['paginaPainel'=>'turmas'])
@include('turmas._resumo_cadastral')
@include('turmas._modelos',['paginaModelos'=>'turmas','gruposExibidos'=>['analises']])

@if(session('sucesso'))<x-aviso tipo="sucesso">{{ session('sucesso') }}</x-aviso>@endif
@if(session('erro'))<x-aviso tipo="erro">{{ session('erro') }}</x-aviso>@endif

<section id="relacao-turmas" class="card card-pad tabela-lista-principal tabela-principal-integrada">


{{-- Tabela --}}
@if($erroLista)
<x-aviso tipo="erro">Não foi possível carregar os registros. Tente novamente usando os filtros acima.</x-aviso>
@else
  <div class="painel-relacao-cabecalho"><h2>Turmas</h2><div role="group" aria-label="Apresentação das turmas"><button type="button" class="btn btn-secondary" data-lista-modo="tabela" aria-pressed="true"><x-icone nome="prova" /> Tabela</button><button type="button" class="btn btn-secondary" data-lista-modo="cards" aria-pressed="false"><x-icone nome="turma" /> Cards</button></div></div>
  <x-tabela :integrada="true" titulo="Turmas" :completa="true" data-painel-lista data-apresentacao="tabela">
<table class="table tabela-padrao" id="turmas-table">
    <caption class="sr-only">Turmas</caption>
    <thead>
      <tr>
        <th scope="col" data-ordenar="texto">Turma</th>
        <th scope="col">Série</th>
        <th scope="col">Turno</th>
        @unless(\App\Support\Acesso::pessoal())<th scope="col">Escola</th>@endunless
        <th scope="col">Alunos</th>
        <th scope="col">Ano letivo</th>
        <th scope="col">Status</th>
        <th scope="col">Ações</th>
      </tr>
    </thead>
    <tbody>
      @forelse($turmas as $t)
        <tr data-nome="{{ strtolower($t['nome']) }}">
          <td data-valor-ordem="{{ $t['nome'] }}" data-rotulo="Turma" class="tabela-principal"><div class="tabela-valor">
            <div class="turma-name">
              <x-avatar-turma :turma="$t" tamanho="sm" />
              <strong>{{ $t['nome'] }}</strong>
            </div>
          </div></td>
          <td data-rotulo="Série"><div class="tabela-valor"><span class="badge badge-info">{{ $t['serie'] }}</span></div></td>
          <td data-rotulo="Turno"><div class="tabela-valor">{{ ['manha'=>'Manhã','tarde'=>'Tarde','noite'=>'Noite','integral'=>'Integral'][$t['turno']] ?? $t['turno'] }}</div></td>
          @unless(\App\Support\Acesso::pessoal())
          <td style="max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="{{ $t['escola_nome'] ?? '' }}" data-rotulo="Escola"><div class="tabela-valor">
            {{ $t['escola_nome'] ?? '—' }}
          </div></td>
          @endunless
          <td style="font-family:var(--font-mono);" data-rotulo="Alunos"><div class="tabela-valor">{{ $t['total_alunos'] ?? 'Não informado' }}</div></td>

          <td style="font-family:var(--font-mono);" data-rotulo="Ano letivo"><div class="tabela-valor">{{ $t['ano_letivo'] }}</div></td>
          <td data-rotulo="Status"><div class="tabela-valor">
            @if($t['ativo'])
              <span class="badge badge-success badge-dot">Em uso</span>
            @else
              <span class="badge badge-danger badge-dot">Arquivada</span>
            @endif
          </div></td>
          <td style="text-align:right;" data-rotulo="Ações" class="tabela-acoes"><div class="tabela-valor">
            <a href="{{ route('turmas.show', $t['id']) }}" class="btn btn-sm btn-secondary">Ver turma</a>
          </div></td>
        </tr>
      @empty
        <tr>
          <td colspan="{{ \App\Support\Acesso::pessoal() ? 7 : 8 }}" style="text-align:center;padding:48px 20px;color:var(--muted);">
            <div class="icone-grande"><x-icone nome="escola" /></div>
            <div style="font-size:15px;font-weight:600;color:var(--fg-2);margin-bottom:6px;">Nenhuma turma encontrada</div>
            <p>@if($haFiltros) Ajuste ou limpe os filtros para encontrar outros registros. @else Crie a primeira turma usando o botão "+ Nova turma". @endif</p>
          </td>
        </tr>
      @endforelse
    </tbody>
  </table>
</x-tabela>

@endif
</section>



@include('turmas._modelos',['paginaModelos'=>'turmas','gruposExibidos'=>['planejamento','ensino','decisoes','administrativo']])

{{-- Modal: criar turma --}}
@pode('turma.gerir')
<div class="modal-backdrop" id="modal-criar" aria-hidden="true">
  <div class="modal" role="dialog" aria-modal="true">
    <div class="modal-head">
      <h2>Nova turma</h2>
      <button type="button" class="modal-close" data-turma-fechar="modal-criar">&times;</button>
    </div>
    <form method="POST" action="{{ route('turmas.store') }}">
      @csrf <x-revisao-acesso />
      <div class="modal-body">
        <div class="form-row-2">
          <div class="field" style="grid-column:1/-1">
            <label for="cr-nome">Nome da turma *</label>
            <input class="input" type="text" id="cr-nome" name="nome" required placeholder="Ex.: 9º A" />
          </div>
          <div class="field">
            <label for="cr-serie">Série *</label>
            <input class="input" type="text" id="cr-serie" name="serie" required placeholder="Ex.: 9º ano" />
          </div>
          <div class="field">
            <label for="cr-turno">Turno *</label>
            <select class="select" id="cr-turno" name="turno" required>
              <option value="">—</option>
              <option value="manha">Manhã</option>
              <option value="tarde">Tarde</option>
              <option value="noite">Noite</option>
              <option value="integral">Integral</option>
            </select>
          </div>
          @unless(\App\Support\Acesso::pessoal())
<div class="field">
            <label for="cr-escola">Escola *</label>
            <select class="select" id="cr-escola" name="escola_id" required>
              <option value="">— Selecione —</option>
              @foreach($escolas as $e)
                <option value="{{ $e['id'] }}">{{ $e['nome'] }}</option>
              @endforeach
            </select>
          </div>
@endunless
          <div class="field">
            <label for="cr-ano">Ano letivo *</label>
            <input class="input num" type="number" id="cr-ano" name="ano_letivo"
                   required min="2020" max="2099" value="{{ date('Y') }}" />
          </div>
        </div>
      </div>
      <div class="modal-foot">
        <button type="button" class="btn btn-ghost" data-turma-fechar="modal-criar">Cancelar</button>
        <button type="submit" class="btn btn-primary">Criar turma</button>
      </div>
    </form>
  </div>
</div>

@endpode

<div style="height:48px;"></div>
@endsection



@push('scripts')
<script src="{{ asset('js/turmas.js') }}?v={{ filemtime(public_path('js/turmas.js')) }}" defer></script>
@endpush
