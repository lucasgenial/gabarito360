@extends('layouts.app')

@section('tela', 'tela-equipe')

@section('title', 'Equipe')

@section('breadcrumb')
<span>Início</span><span class="sep">/</span><span>Equipe</span>
@endsection

@section('content')

@php
  $perfilLabels = [
    'admin_rede'  => 'Administrador da Rede',
    'dir_nucleo'  => 'Diretor de Núcleo',
    'dir_escolar' => 'Diretor Escolar',
    'coordenador' => 'Coordenador Pedagógico',
    'professor'   => 'Professor',
    'aluno'       => 'Aluno',
  ];
  $badgeMap = [
    'admin_rede'  => 'badge-info',
    'dir_nucleo'  => 'badge-warn',
    'dir_escolar' => 'badge-purple',
    'coordenador' => 'badge-amber',
    'professor'   => 'badge-info',
    'aluno'       => 'badge-success',
  ];
@endphp

<x-pagina titulo="Equipe">
  <x-slot:sub>{{ session('auth_vinculos') ? 'Gerencie os vínculos da equipe neste contexto.' : 'Gerencie os usuários e perfis de acesso do sistema.' }}</x-slot:sub>
    <a href="{{ route('membros.perfis') }}" class="btn btn-ghost">O que cada perfil pode</a>
    @if(session('auth_vinculos')) @pode('usuario.gerir')<a class="btn btn-secondary" href="{{ route('membros.convite-vinculo') }}">Convidar conta existente</a>@endpode @endif
    @pode('usuario.gerir')<a href="{{ route('membros.create') }}" class="btn btn-primary">+ Novo Membro</a>@endpode
</x-pagina>

@if(session('sucesso'))<x-aviso tipo="sucesso">{{ session('sucesso') }}</x-aviso>@endif
@if(session('erro'))<x-aviso tipo="erro">{{ session('erro') }}</x-aviso>@endif

{{-- KPIs por perfil --}}
<div class="kpi-strip" style="margin-top:20px;">
  @foreach(['professor','coordenador','dir_escolar','admin_rede'] as $pf)
    <div class="kpi-card-role">
      <div class="row-between">
        <div class="kpi-role">{{ $perfilLabels[$pf] }}</div>
        <span class="badge {{ $badgeMap[$pf] }}">{{ $pf }}</span>
      </div>
      <div class="kpi-num">{{ $totais[$pf] ?? 0 }}</div>
      <div class="kpi-meta">{{ ($totais[$pf] ?? 0) === 1 ? 'membro' : 'membros' }}</div>
    </div>
  @endforeach
</div>

{{-- Filtros --}}
<form method="GET" action="{{ route('membros.index') }}" class="filter-bar filtros-cel">
  <input class="input" type="text" name="busca" placeholder="Buscar por nome ou e-mail…" value="{{ $filtroBusc }}">
  <select class="select" name="perfil" style="width:auto;">
    <option value="">Todos os perfis</option>
    @foreach($perfis as $key => $cfg)
      <option value="{{ $key }}" {{ $filtroPerf === $key ? 'selected' : '' }}>{{ $cfg['label'] }}</option>
    @endforeach
  </select>
  <button type="submit" class="btn btn-secondary">Filtrar</button>
  @if($filtroBusc || $filtroPerf)
    <a href="{{ route('membros.index') }}" class="btn btn-ghost">Limpar</a>
  @endif
</form>

{{-- Tabela de membros agrupada por perfil --}}
@if($agrupados->isEmpty())
  <div style="text-align:center;padding:40px;color:var(--muted);">
    Nenhum membro encontrado.
  </div>
@else
  @foreach($perfis as $perfilKey => $perfilCfg)
    @if($agrupados->has($perfilKey))
      @php $membros = $agrupados->get($perfilKey); @endphp
      <div class="card card-pad" style="margin-bottom:20px;">
        <div class="row-between" style="margin-bottom:14px;">
          <h2 style="font-size:16px;font-weight:700;">{{ $perfilCfg['label'] }}</h2>
          <span class="badge {{ $perfilCfg['badge'] }}">{{ $membros->count() }} {{ $membros->count() === 1 ? 'membro' : 'membros' }}</span>
        </div>
        <table class="table lista-cel">
          <thead>
            <tr>
              <th>Membro</th>
              <th>E-mail</th>
              <th>Desde</th>
              <th>Status</th>
              <th>Ações</th>
            </tr>
          </thead>
          <tbody>
            @foreach($membros as $m)
              @php
                $ini = implode('', array_map(fn($p) => strtoupper($p[0]), array_slice(array_filter(explode(' ', $m['nome'])), 0, 2)));
              @endphp
              <tr>
                <td>
                  <div class="member-cell">
                    <div class="member-avatar">{{ $ini }}</div>
                    <div>
                      <div class="member-name">{{ $m['nome'] }}</div>
                      @if(isset($m['vinculo_id']))<small>{{ $m['espaco_nome'] ?? '' }} · Vínculo #{{ $m['vinculo_id'] }}</small>@endif
                    </div>
                  </div>
                </td>
                <td class="mono" style="font-size:13px;">{{ $m['email'] }}</td>
                <td class="num">{{ !empty($m['created_at']) ? \Carbon\Carbon::parse($m['created_at'])->format('d/m/Y') : '—' }}</td>
                <td>
                  @if(isset($m['situacao']))
                    <span class="badge badge-info">{{ ucfirst($m['situacao']) }}</span>
                  @elseif($m['ativo'])
                    <span class="badge badge-success">Ativo</span>
                  @else
                    <span class="badge badge-danger">Inativo</span>
                  @endif
                </td>
                <td>
                  <div style="display:flex;gap:6px;flex-wrap:wrap;">
                    <a href="{{ route('membros.edit', array_filter(['id' => $m['id'], 'vinculo_id' => $m['vinculo_id'] ?? null])) }}" class="btn btn-secondary btn-sm">Editar</a>
                    <a href="{{ route('membros.permissoes', array_filter(['id' => $m['id'], 'vinculo_id' => $m['vinculo_id'] ?? null])) }}" class="btn btn-ghost btn-sm">Permissões</a>
                    @if(!isset($m['situacao']) || in_array($m['situacao'], ['ativo', 'suspenso']))
                    <form method="POST" action="{{ route('membros.toggle', array_filter(['id' => $m['id'], 'vinculo_id' => $m['vinculo_id'] ?? null])) }}" style="display:inline;">
                      @csrf
                      <button type="submit" class="btn btn-ghost btn-sm">
                        {{ isset($m['situacao']) ? ($m['situacao'] === 'ativo' ? 'Suspender vínculo' : 'Reativar vínculo') : ($m['ativo'] ? 'Desativar' : 'Ativar') }}
                      </button>
                    </form>
                    @endif
                  </div>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    @endif
  @endforeach
@endif

<div style="height:48px"></div>
@endsection
