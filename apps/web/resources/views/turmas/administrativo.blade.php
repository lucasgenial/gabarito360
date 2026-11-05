@extends('layouts.app')
@section('title', $turma['nome'])
@section('breadcrumb')
<a href="{{ route('turmas.index') }}">Turmas</a><span class="sep">/</span><span>{{ $turma['nome'] }}</span>
@endsection
@section('content')
<x-pagina :titulo="$turma['nome']">
  <x-slot:sub>{{ $turma['escola_nome'] }} · {{ $turma['ano_letivo'] }} · {{ $turma['total_alunos'] }} aluno(s) ativo(s)</x-slot:sub>
  @if(\App\Support\Acesso::pode('aluno.ver'))<a class="btn btn-secondary" href="{{ route('alunos.index', ['turma_id' => $turma['id']]) }}">Gerenciar alunos</a>@endif
</x-pagina>
@if(session('sucesso'))<x-aviso tipo="sucesso">{{ session('sucesso') }}</x-aviso>@endif
@if(session('erro'))<x-aviso tipo="erro">{{ session('erro') }}</x-aviso>@endif
<section class="card card-pad">
  <h2>Dados da turma</h2>
  <p>Status: {{ $turma['ativo'] ? 'Ativa' : 'Inativa' }}</p>
  @if(\App\Support\Acesso::pode('turma.gerir'))
  <form method="POST" action="{{ route('turmas.update', $turma['id']) }}">
    @csrf @method('PUT')
    <div class="form-row-2">
      <div class="field"><label for="nome">Nome da turma</label><input class="input" id="nome" name="nome" required maxlength="50" value="{{ old('nome', $turma['nome']) }}"></div>
      <div class="field"><label for="serie">Série</label><input class="input" id="serie" name="serie" required maxlength="50" value="{{ old('serie', $turma['serie']) }}"></div>
      <div class="field"><label for="turno">Turno</label><select class="select" id="turno" name="turno" required>
        @foreach(['manha'=>'Manhã','tarde'=>'Tarde','noite'=>'Noite','integral'=>'Integral'] as $valor => $rotulo)
        <option value="{{ $valor }}" @selected(old('turno', $turma['turno']) === $valor)>{{ $rotulo }}</option>
        @endforeach
      </select></div>
      <div class="field"><label for="ano">Ano letivo</label><input class="input" id="ano" name="ano_letivo" type="number" min="2020" max="2099" required value="{{ old('ano_letivo', $turma['ano_letivo']) }}"></div>
    </div>
    <button class="btn btn-primary" type="submit">Salvar alterações</button>
  </form>
  @endif
  @if(\App\Support\Acesso::pode('turma.desativar'))
  <form method="POST" action="{{ route('turmas.toggle', $turma['id']) }}" style="margin-top:16px;">
    @csrf <button class="btn btn-secondary" type="submit">{{ $turma['ativo'] ? 'Desativar turma' : 'Ativar turma' }}</button>
  </form>
  @endif
</section>
<section class="card card-pad" style="margin-top:20px;">
  <h2>Alunos</h2>
  <x-tabela :integrada="true" titulo="Alunos da turma" modo="lista"><table class="tabela-padrao table"><caption class="sr-only">Alunos da turma</caption><thead><tr><th scope="col">Nome</th><th scope="col">Matrícula</th><th scope="col">Status</th></tr></thead><tbody>
    @forelse($turma['alunos'] as $aluno)
    <tr><td class="tabela-principal" data-rotulo="Nome"><div class="tabela-valor">{{ $aluno['nome'] }}</div></td><td data-rotulo="Matrícula"><div class="tabela-valor">{{ $aluno['matricula'] }}</div></td><td data-rotulo="Status"><div class="tabela-valor">{{ $aluno['ativo'] ? 'Ativo' : 'Inativo' }}</div></td></tr>
    @empty <tr><td colspan="3">Nenhum aluno cadastrado nesta turma.</td></tr> @endforelse
  </tbody></table></x-tabela>
</section>
@endsection
