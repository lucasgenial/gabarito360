@extends('layouts.app')

@section('tela', 'tela-alunos')

@section('title', 'Alunos')

@section('breadcrumb')
<a href="{{ route('painel') }}">Início</a><span class="sep">/</span><span>Alunos</span>
@endsection

@section('content')

<x-pagina titulo="Alunos">
  <x-slot:sub>@if($erroLista)Listagem temporariamente indisponível
  @else
  <b>{{ $kpis['total_alunos'] ?? 0 }}</b> aluno(s) cadastrado(s)
  @endif
  </x-slot:sub>
  @pode('aluno.gerir')<a href="{{ route('alunos.create') }}" class="btn btn-primary">+ Cadastrar aluno</a>@endpode
</x-pagina>

@if(session('sucesso'))<x-aviso tipo="sucesso">{{ session('sucesso') }}</x-aviso>@endif

<section class="card card-pad tabela-lista-principal tabela-principal-integrada">
{{-- Filtros --}}
<form method="GET" class="filtros-cel tabela-filtros-principal" action="{{ route('alunos.index') }}" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;">
  <div style="position:relative;flex:1;min-width:200px;">
    <svg style="position:absolute;left:12px;top:50%;transform:translateY(-50%);opacity:.4;" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
    <input class="input" type="search" name="busca" aria-label="Buscar aluno por nome ou matrícula" placeholder="Buscar aluno por nome ou matrícula…"
           value="{{ $busca }}" style="padding-left:36px;" />
  </div>
  <select class="select" name="turma_id" aria-label="Filtrar por turma" style="min-width:180px;">
    <option value="">Todas as turmas</option>
    @foreach($turmas as $t)
      <option value="{{ $t['id'] }}" {{ $turma_id == $t['id'] ? 'selected' : '' }}>{{ $t['nome'] }} — {{ $t['serie'] }}</option>
    @endforeach
  </select>
  <button type="submit" class="btn btn-secondary">Filtrar</button>
  @if($haFiltros)
    <a href="{{ route('alunos.index') }}" class="btn btn-ghost">Limpar</a>
  @endif
</form>

{{-- Tabela --}}
@if($erroLista)
<x-aviso tipo="erro">Não foi possível carregar os registros. Tente novamente usando os filtros acima.</x-aviso>
@else
  <x-tabela :integrada="true" titulo="Alunos" :completa="true">
<table class="table tabela-padrao">
    <caption class="sr-only">Alunos</caption>
    <thead>
      <tr>
        <th scope="col" data-ordenar="texto">Aluno</th>
        <th scope="col">Matrícula</th>
        <th scope="col">Turma</th>
        <th scope="col">Série</th>
        <th scope="col">Status</th>
        <th scope="col">Ações</th>
      </tr>
    </thead>
    <tbody>
      @forelse($alunos as $a)
        <tr>
          <td data-valor-ordem="{{ $a['nome'] }}" data-rotulo="Aluno" class="tabela-principal"><div class="tabela-valor">
            <div class="student-name">
              <x-avatar :avatar-id="$a['avatar_id'] ?? null" :nome="$a['nome']" :aluno-id="$a['id']" :tem-foto="$a['tem_foto']??false" :imagem-versao="$a['imagem_versao']??1" tamanho="sm" />
              <strong>{{ $a['nome'] }}</strong>
            </div>
          </div></td>
          <td style="font-family:var(--font-mono);" data-rotulo="Matrícula"><div class="tabela-valor">{{ $a['matricula'] }}</div></td>
          <td data-rotulo="Turma"><div class="tabela-valor">{{ $a['turma_nome'] ?? '—' }}</div></td>
          <td data-rotulo="Série"><div class="tabela-valor">{{ $a['turma_serie'] ?? '—' }}</div></td>
          <td data-rotulo="Status"><div class="tabela-valor">
            @if($a['ativo'])
              <span class="badge badge-success badge-dot">Ativo</span>
            @else
              <span class="badge badge-danger badge-dot">Inativo</span>
            @endif
          </div></td>
          <td style="text-align:right;" data-rotulo="Ações" class="tabela-acoes"><div class="tabela-valor">
            <a href="{{ route('alunos.show', $a['id']) }}" class="btn btn-sm btn-secondary">Ver</a>
          </div></td>
        </tr>
      @empty
        <tr>
          <td colspan="6" style="text-align:center;padding:48px 20px;color:var(--muted);">
            <div class="icone-grande"><x-icone nome="aluno" /></div>
            <div style="font-size:15px;font-weight:600;color:var(--fg-2);margin-bottom:6px;">Nenhum aluno encontrado</div>
            <p>@if($haFiltros) Ajuste ou limpe os filtros para encontrar outros registros. @else Cadastre o primeiro aluno usando o botão "+ Cadastrar aluno". @endif</p>
          </td>
        </tr>
      @endforelse
    </tbody>
  </table>
</x-tabela>
@endif
</section>

<div style="height:48px;"></div>
@endsection
