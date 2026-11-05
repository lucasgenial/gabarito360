@extends('layouts.app')

@section('tela', 'tela-provas')

@section('title', 'Provas e gabaritos')

@section('breadcrumb')
<a href="{{ route('painel') }}">Início</a><span class="sep">/</span><span>Provas</span>
@endsection

@section('content')

<x-pagina titulo="Provas e gabaritos">
  <x-slot:sub>@if($erroLista)Listagem temporariamente indisponível
  @else
  <b>{{ count($provas) }}</b> prova(s) encontrada(s)
  @endif
  </x-slot:sub>
  @pode('prova.criar')<a href="{{ route('provas.create') }}" class="btn btn-primary">+ Nova prova</a>@endpode
</x-pagina>

@if(session('sucesso'))<x-aviso tipo="sucesso">{{ session('sucesso') }}</x-aviso>@endif
@if(session('erro'))<x-aviso tipo="erro">{{ session('erro') }}</x-aviso>@endif

<section class="card card-pad tabela-lista-principal tabela-principal-integrada">
{{-- Filtros --}}
<form method="GET" class="filtros-cel tabela-filtros-principal" action="{{ route('provas.index') }}" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;">
  <div style="position:relative;flex:1;min-width:220px;">
    <svg style="position:absolute;left:12px;top:50%;transform:translateY(-50%);opacity:.4;" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
    <input class="input" type="search" name="busca" aria-label="Buscar prova por título" placeholder="Buscar prova por título…"
           value="{{ $busca }}" style="padding-left:36px;" />
  </div>
  @php($disciplinasFiltro = $disciplinasFiltro ?? [])
  <select class="select" name="disciplina" aria-label="Filtrar por disciplina ou área informada" style="min-width:160px;">
    <option value="">Todas as disciplinas/áreas</option>
    @if($disciplina !== '' && !in_array($disciplina, $disciplinasFiltro, true))
      <option value="{{ $disciplina }}" selected>{{ $disciplina }} (filtro atual sem resultados)</option>
    @endif
    @foreach($disciplinasFiltro as $d)
      <option value="{{ $d }}" {{ $disciplina === $d ? 'selected' : '' }}>{{ $d }}</option>
    @endforeach
  </select>
  <select class="select" name="status" aria-label="Filtrar por situação da prova" style="min-width:150px;">
    <option value="">Todos os status</option>
    <option value="rascunho"    {{ $status==='rascunho'    ? 'selected' : '' }}>Rascunho</option>
    <option value="publicada"   {{ $status==='publicada'   ? 'selected' : '' }}>Publicada</option>
    <option value="em_correcao" {{ $status==='em_correcao' ? 'selected' : '' }}>Em correção</option>
    <option value="corrigida"   {{ $status==='corrigida'   ? 'selected' : '' }}>Corrigida</option>
  </select>
  <button type="submit" class="btn btn-secondary">Filtrar</button>
  @if($haFiltros)
    <a href="{{ route('provas.index') }}" class="btn btn-ghost">Limpar</a>
  @endif
</form>

{{-- Tabela --}}
@if($erroLista)
<x-aviso tipo="erro">Não foi possível carregar os registros. Tente novamente usando os filtros acima.</x-aviso>
@else
  <x-tabela :integrada="true" titulo="Provas e gabaritos" :completa="true">
<table class="table tabela-padrao">
    <caption class="sr-only">Provas e gabaritos</caption>
    <thead>
      <tr>
        <th scope="col" data-ordenar="texto">Prova</th>
        <th scope="col" class="hide-sm">Turmas</th>
        <th scope="col" class="hide-sm">Aplicação</th>
        <th scope="col">Status</th>
        <th scope="col">Progresso</th>
        <th scope="col">Ações</th>
      </tr>
    </thead>
    <tbody>
      @forelse($provas as $p)
        @php
          $statusCfg = [
            'rascunho'    => ['cls'=>'badge-info',   'label'=>'Rascunho'],
            'publicada'   => ['cls'=>'badge-success', 'label'=>'Publicada'],
            'em_correcao' => ['cls'=>'badge-warn', 'label'=>'Em correção', 'style'=>''],
            'corrigida'   => ['cls'=>'badge-success', 'label'=>'Corrigida'],
          ];
          $sc = $statusCfg[$p['status']] ?? ['cls'=>'badge-info','label'=>$p['status']];
        @endphp
        <tr>
          <td data-valor-ordem="{{ $p['titulo'] }}" data-rotulo="Prova" class="tabela-principal"><div class="tabela-valor">
            <div style="display:flex;align-items:center;gap:10px;">
              <div style="width:36px;height:36px;border-radius:8px;background:var(--accent-light);color:var(--accent-dark);display:grid;place-items:center;flex-shrink:0;"><x-icone nome="prova" /></div>
              <div>
                <div style="font-weight:600;">{{ $p['titulo'] }}</div>
                <div class="exam-meta">{{ $p['disciplina'] }} · {{ $p['serie'] }} · {{ $p['num_questoes'] }} questões</div>
                <div class="exam-meta">Criada por: {{ $p['criado_por_nome'] ?? 'Autoria não registrada' }}</div>
              </div>
            </div>
          </div></td>
          <td class="hide-sm" data-rotulo="Turmas"><div class="tabela-valor">
            @if(count($p['turmas'] ?? []) > 0)
              <span style="color:var(--fg-2);font-size:13px;">{{ implode(' · ', array_slice($p['turmas'], 0, 3)) }}</span>
              @if(count($p['turmas']) > 3)
                <span style="color:var(--muted);font-size:12px;"> +{{ count($p['turmas'])-3 }}</span>
              @endif
            @else
              <span style="color:var(--muted);font-size:13px;">—</span>
            @endif
          </div></td>
          <td class="hide-sm" style="font-size:13px;" data-rotulo="Aplicação"><div class="tabela-valor">
            {{ $p['data_aplicacao'] ? \Carbon\Carbon::parse($p['data_aplicacao'])->format('d/m/Y') : '—' }}
          </div></td>
          <td data-rotulo="Status"><div class="tabela-valor">
            <span class="badge {{ $sc['cls'] }}" style="{{ $sc['style'] ?? '' }}">{{ $sc['label'] }}</span>
          </div></td>
          <td data-rotulo="Progresso"><div class="tabela-valor">
            @php $prog = $p['progresso'] ?? ['tipo' => 'nenhum']; @endphp
            @if($prog['tipo'] === 'media')
              @php
                $cor = $prog['media'] === null ? 'var(--muted)' : ($prog['media'] >= 7 ? 'var(--success)' : ($prog['media'] >= 5 ? 'var(--warn)' : 'var(--danger)'));
                $pct = $prog['media'] !== null ? min(100, $prog['media'] * 10) : 0;
              @endphp
              <div style="display:flex;align-items:center;">
                <span class="mini-bar" style="width:76px;height:7px;background:var(--surface-2);border-radius:99px;overflow:hidden;display:inline-block;margin-right:8px;"><div style="height:100%;width:{{ $pct }}%;background:{{ $cor }};"></div></span>
                <span style="font-family:var(--font-mono);">{{ $prog['media'] !== null ? number_format($prog['media'], 1, ',', '') : '—' }}</span>
              </div>
              <div class="exam-meta">{{ $prog['cartoes_lidos'] }}/{{ $prog['cartoes_total'] }} cartões</div>
            @elseif($prog['tipo'] === 'correcao')
              @php $pct = $prog['total'] > 0 ? round($prog['lidos'] / $prog['total'] * 100) : 0; @endphp
              <div style="display:flex;align-items:center;">
                <span class="mini-bar" style="width:76px;height:7px;background:var(--surface-2);border-radius:99px;overflow:hidden;display:inline-block;margin-right:8px;"><div style="height:100%;width:{{ $pct }}%;background:var(--accent);"></div></span>
                <span style="font-family:var(--font-mono);">{{ $prog['lidos'] }}/{{ $prog['total'] }}</span>
              </div>
              <div class="exam-meta">cartões lidos</div>
            @elseif($prog['tipo'] === 'gabarito')
              @php $pct = $prog['total'] > 0 ? round($prog['preenchidas'] / $prog['total'] * 100) : 0; @endphp
              <div style="display:flex;align-items:center;">
                <span class="mini-bar" style="width:76px;height:7px;background:var(--surface-2);border-radius:99px;overflow:hidden;display:inline-block;margin-right:8px;"><div style="height:100%;width:{{ $pct }}%;background:var(--muted);"></div></span>
                <span style="font-family:var(--font-mono);">{{ $prog['preenchidas'] }}/{{ $prog['total'] }}</span>
              </div>
              <div class="exam-meta">questões no gabarito</div>
            @else
              <span style="color:var(--muted);font-size:13px;">Aguardando aplicação</span>
            @endif
          </div></td>
          {{-- A ação reutiliza o rascunho no mesmo espaço, sem sair para outra página. --}}
          <td style="text-align:right;white-space:nowrap;" data-rotulo="Ações" class="tabela-acoes"><div class="tabela-valor">
            @if($p['status'] === 'rascunho' && ($p['pode_editar'] ?? true) && \App\Support\Acesso::pode('prova.editar'))
              <form method="POST" action="{{ route('provas.publicar', $p['id']) }}" style="display:inline;">
                @csrf
                <button type="submit" class="btn btn-sm btn-secondary" title="Publicar">Publicar</button>
              </form>
            @endif
            @if($p['status'] === 'em_correcao')
              <a href="{{ route('correcao.show', $p['id']) }}" class="btn btn-sm btn-secondary" style="margin-left:6px;">Acompanhar</a>
            @elseif($p['status'] === 'corrigida')
              <a href="{{ route('relatorios.prova', $p['id']) }}" class="btn btn-sm btn-secondary" style="margin-left:6px;">Relatório</a>
            @else
              <a href="{{ route('provas.show', $p['id']) }}" class="btn btn-sm btn-secondary" style="margin-left:6px;">Ver</a>
            @endif
            @if($p['pode_copiar'] ?? false)
              @include('provas._botao-copiar',['origem'=>$p])
            @endif
          </div></td>
        </tr>
      @empty
        <tr>
          <td colspan="6" style="text-align:center;padding:48px 20px;color:var(--muted);">
            <div class="icone-grande"><x-icone nome="prova" /></div>
            <div style="font-size:15px;font-weight:600;color:var(--fg-2);margin-bottom:6px;">Nenhuma prova encontrada</div>
            <p>@if($haFiltros) Ajuste ou limpe os filtros para encontrar outros registros. @elseif(\App\Support\Acesso::pode('prova.criar')) Crie a primeira prova usando o botão "+ Nova prova". @else As provas disponíveis neste espaço aparecerão aqui. @endif</p>
          </td>
        </tr>
      @endforelse
    </tbody>
  </table>
</x-tabela>
@endif
</section>

<div style="height:48px;"></div>
@include('provas._modal-copiar')
@endsection
