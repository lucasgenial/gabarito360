@extends('layouts.app')

@section('tela', 'tela-alunos')

@section('title', 'Alunos')

@section('breadcrumb')
<a href="{{ route('painel') }}">Início</a><span class="sep">/</span><span>Alunos</span>
@endsection

@section('content')

<x-pagina titulo="Alunos">
  <x-slot:sub><b>{{ $kpis['total_alunos'] ?? 0 }}</b> aluno(s) cadastrado(s)</x-slot:sub>
  <a href="{{ route('alunos.create') }}" class="btn btn-primary">+ Cadastrar aluno</a>
</x-pagina>

@if(session('sucesso'))<x-aviso tipo="sucesso">{{ session('sucesso') }}</x-aviso>@endif

{{-- Filtros --}}
<form method="GET" class="filtros-cel" action="{{ route('alunos.index') }}" style="display:flex;gap:12px;margin-top:20px;flex-wrap:wrap;align-items:flex-end;">
  <div style="position:relative;flex:1;min-width:200px;">
    <svg style="position:absolute;left:12px;top:50%;transform:translateY(-50%);opacity:.4;" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
    <input class="input" type="search" name="busca" placeholder="Buscar aluno por nome ou matrícula…"
           value="{{ $busca }}" style="padding-left:36px;" />
  </div>
  <select class="select" name="turma_id" style="min-width:180px;">
    <option value="">Todas as turmas</option>
    @foreach($turmas as $t)
      <option value="{{ $t['id'] }}" {{ $turma_id == $t['id'] ? 'selected' : '' }}>{{ $t['nome'] }} — {{ $t['serie'] }}</option>
    @endforeach
  </select>
  <button type="submit" class="btn btn-secondary">Filtrar</button>
  @if($busca || $turma_id)
    <a href="{{ route('alunos.index') }}" class="btn btn-ghost">Limpar</a>
  @endif
</form>

{{-- Tabela --}}
<div class="card" style="margin-top:18px;overflow:hidden;">
  <table class="table lista-cel">
    <thead>
      <tr>
        <th>Aluno</th>
        <th>Matrícula</th>
        <th>Turma</th>
        <th>Série</th>
        <th>Status</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
      @forelse($alunos as $a)
        <tr>
          <td>
            <div class="student-name">
              <x-avatar :nome="$a['nome']" :aluno-id="$a['id']" tamanho="sm" />
              <strong>{{ $a['nome'] }}</strong>
            </div>
          </td>
          <td style="font-family:var(--font-mono);">{{ $a['matricula'] }}</td>
          <td>{{ $a['turma_nome'] ?? '—' }}</td>
          <td>{{ $a['turma_serie'] ?? '—' }}</td>
          <td>
            @if($a['ativo'])
              <span class="badge badge-success badge-dot">Ativo</span>
            @else
              <span class="badge badge-danger badge-dot">Inativo</span>
            @endif
          </td>
          <td style="text-align:right;">
            <a href="{{ route('alunos.show', $a['id']) }}" class="btn btn-sm btn-secondary">Ver</a>
          </td>
        </tr>
      @empty
        <tr>
          <td colspan="6" style="text-align:center;padding:48px 20px;color:var(--muted);">
            <div class="icone-grande"><x-icone nome="aluno" /></div>
            <div style="font-size:15px;font-weight:600;color:var(--fg-2);margin-bottom:6px;">Nenhum aluno encontrado</div>
            <p>Cadastre o primeiro aluno usando o botão "+ Cadastrar aluno".</p>
          </td>
        </tr>
      @endforelse
    </tbody>
  </table>
</div>

<div style="height:48px;"></div>
@endsection
