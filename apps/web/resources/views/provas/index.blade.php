@extends('layouts.app')

@section('tela', 'tela-provas')

@section('title', 'Provas e Gabaritos')

@section('breadcrumb')
<a href="{{ route('painel') }}">Início</a><span class="sep">/</span><span>Provas</span>
@endsection

@section('content')

<x-pagina titulo="Provas e gabaritos">
  <x-slot:sub><b>{{ count($provas) }}</b> prova(s) encontrada(s)</x-slot:sub>
  @pode('prova.criar')<a href="{{ route('provas.create') }}" class="btn btn-primary">+ Nova prova</a>@endpode
</x-pagina>

@if(session('sucesso'))<x-aviso tipo="sucesso">{{ session('sucesso') }}</x-aviso>@endif
@if(session('erro'))<x-aviso tipo="erro">{{ session('erro') }}</x-aviso>@endif

{{-- Filtros --}}
<form method="GET" class="filtros-cel" action="{{ route('provas.index') }}" style="display:flex;gap:12px;margin-top:20px;flex-wrap:wrap;align-items:flex-end;">
  <div style="position:relative;flex:1;min-width:220px;">
    <svg style="position:absolute;left:12px;top:50%;transform:translateY(-50%);opacity:.4;" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
    <input class="input" type="search" name="busca" placeholder="Buscar prova por título…"
           value="{{ $busca }}" style="padding-left:36px;" />
  </div>
  <select class="select" name="disciplina" style="min-width:160px;">
    <option value="">Todas as disciplinas</option>
    @foreach(['Matemática','Português','Ciências','História','Geografia','Inglês','Ed. Física','Arte'] as $d)
      <option value="{{ $d }}" {{ $disciplina === $d ? 'selected' : '' }}>{{ $d }}</option>
    @endforeach
  </select>
  <select class="select" name="status" style="min-width:150px;">
    <option value="">Todos os status</option>
    <option value="rascunho"    {{ $status==='rascunho'    ? 'selected' : '' }}>Rascunho</option>
    <option value="publicada"   {{ $status==='publicada'   ? 'selected' : '' }}>Publicada</option>
    <option value="em_correcao" {{ $status==='em_correcao' ? 'selected' : '' }}>Em correção</option>
    <option value="corrigida"   {{ $status==='corrigida'   ? 'selected' : '' }}>Corrigida</option>
  </select>
  <button type="submit" class="btn btn-secondary">Filtrar</button>
  @if($busca || $disciplina || $status)
    <a href="{{ route('provas.index') }}" class="btn btn-ghost">Limpar</a>
  @endif
</form>

{{-- Tabela --}}
<div class="card" style="margin-top:18px;overflow:hidden;">
  <table class="table lista-cel">
    <thead>
      <tr>
        <th>Prova</th>
        <th class="hide-sm">Turmas</th>
        <th class="hide-sm">Aplicação</th>
        <th>Status</th>
        <th>Progresso</th>
        <th></th>
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
          <td>
            <div style="display:flex;align-items:center;gap:10px;">
              <div style="width:36px;height:36px;border-radius:8px;background:var(--accent-light);color:var(--accent-dark);display:grid;place-items:center;flex-shrink:0;"><x-icone nome="prova" /></div>
              <div>
                <div style="font-weight:600;">{{ $p['titulo'] }}</div>
                <div class="exam-meta">{{ $p['disciplina'] }} · {{ $p['serie'] }} · {{ $p['num_questoes'] }} questões</div>
              </div>
            </div>
          </td>
          <td class="hide-sm">
            @if(count($p['turmas'] ?? []) > 0)
              <span style="color:var(--fg-2);font-size:13px;">{{ implode(' · ', array_slice($p['turmas'], 0, 3)) }}</span>
              @if(count($p['turmas']) > 3)
                <span style="color:var(--muted);font-size:12px;"> +{{ count($p['turmas'])-3 }}</span>
              @endif
            @else
              <span style="color:var(--muted);font-size:13px;">—</span>
            @endif
          </td>
          <td class="hide-sm" style="font-size:13px;">
            {{ $p['data_aplicacao'] ? \Carbon\Carbon::parse($p['data_aplicacao'])->format('d/m/Y') : '—' }}
          </td>
          <td>
            <span class="badge {{ $sc['cls'] }}" style="{{ $sc['style'] ?? '' }}">{{ $sc['label'] }}</span>
          </td>
          <td>
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
          </td>
          <td style="text-align:right;white-space:nowrap;">
            @if($p['status'] === 'rascunho' && \App\Support\Acesso::pode('prova.editar'))
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
          </td>
        </tr>
      @empty
        <tr>
          <td colspan="6" style="text-align:center;padding:48px 20px;color:var(--muted);">
            <div class="icone-grande"><x-icone nome="prova" /></div>
            <div style="font-size:15px;font-weight:600;color:var(--fg-2);margin-bottom:6px;">Nenhuma prova encontrada</div>
            <p>Crie a primeira prova usando o botão "+ Nova prova".</p>
          </td>
        </tr>
      @endforelse
    </tbody>
  </table>
</div>

<div style="height:48px;"></div>
@endsection
