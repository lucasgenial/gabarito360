@extends('layouts.app')

@section('tela', 'tela-turmas')

@section('title', 'Turmas')

@section('breadcrumb')
<a href="{{ route('painel') }}">Início</a><span class="sep">/</span><span>Turmas</span>
@endsection

@section('content')

<x-pagina titulo="Turmas">
    <button class="btn btn-secondary" type="button" data-turmas-filtros-abrir aria-label="Filtrar turmas" title="Filtrar turmas"><x-icone nome="filtro" /></button>
    @pode('turma.gerir')<button class="btn btn-primary" data-turma-abrir="modal-criar">+ Nova turma</button>@endpode
</x-pagina>

@if($haFiltros)
  <p class="tw2-filtros-aplicados">Dados filtrados:
    @if($busca) Busca: {{ $busca }}; @endif
    @if($escola_id && !\App\Support\Acesso::pessoal()) escola: {{ collect($escolas)->firstWhere('id', $escola_id)['nome'] ?? 'selecionada' }}; @endif
    @if($ano_letivo) ano letivo: {{ $ano_letivo }}. @endif
  </p>
@endif
<dialog id="filtros-turmas" class="tw2-filtros-dialog" aria-labelledby="filtros-turmas-titulo">
  <form method="GET" action="{{ route('turmas.index') }}">
    <div class="modal-head"><h2 id="filtros-turmas-titulo">Filtrar turmas</h2><button class="modal-close" type="button" data-turmas-filtros-fechar aria-label="Fechar filtros">&times;</button></div>
    <div class="modal-body">
      <div class="field"><label for="filtros-turmas-busca">Nome ou série</label><input class="input" type="search" id="filtros-turmas-busca" name="busca" value="{{ $busca }}" placeholder="Buscar turma por nome ou série…" /></div>
      @unless(\App\Support\Acesso::pessoal())
      <div class="field"><label for="filtros-turmas-escola">Escola</label><select class="select" id="filtros-turmas-escola" name="escola_id"><option value="">Todas as escolas</option>@foreach($escolas as $e)<option value="{{ $e['id'] }}" @selected($escola_id == $e['id'])>{{ $e['nome'] }}</option>@endforeach</select></div>
      @endunless
      <div class="field"><label for="filtros-turmas-ano">Ano letivo</label><input class="input" type="number" id="filtros-turmas-ano" name="ano_letivo" value="{{ $ano_letivo }}" min="2020" max="2099" /></div>
    </div>
    <div class="modal-foot"><button class="btn btn-ghost" type="button" data-turmas-filtros-limpar>Limpar campos</button><button class="btn btn-secondary" type="button" data-turmas-filtros-fechar>Cancelar</button><button class="btn btn-primary" type="submit">Aplicar filtros</button></div>
  </form>
</dialog>
@include('turmas._organizar_painel',['paginaPainel'=>'turmas'])
@include('turmas._resumo_cadastral')

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
        <th scope="col" class="tabela-coluna-acoes">Ações</th>
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
          <td title="{{ $t['escola_nome'] ?? '' }}" data-rotulo="Escola"><div class="tabela-valor">
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

          <td data-rotulo="Ações" class="tabela-acoes"><div class="tabela-valor">
            <a href="{{ route('turmas.show', $t['id']) }}" class="btn btn-sm btn-secondary"><x-icone nome="ver" /> Ver turma</a>
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



@include('turmas._modelos',['paginaModelos'=>'turmas','gruposExibidos'=>['analises']])
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
<script src="@ativo('js/turmas.js')" defer></script>
<script src="@ativo('js/turmas-filtros.js')" defer></script>
@endpush
