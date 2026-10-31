@extends('layouts.public')
@section('title', 'Fale conosco — Gabarito360')

@section('content')
<div class="fale-conosco">
  <h1>Fale conosco</h1>
  <p class="fale-sub">Conte o que você precisa. Respondemos pelo e-mail informado.</p>

  @if($plano)
    <p class="fale-plano">Plano de interesse: <b>{{ $plano['nome'] }}</b></p>
  @endif

  @error('lead')<x-aviso tipo="erro">{{ $message }}</x-aviso>@enderror

  <form method="POST" action="{{ route('leads.store') }}">
    @csrf
    @if($plano)<input type="hidden" name="plano_id" value="{{ $plano['id'] }}">@endif

    {{-- Armadilha para robôs: fica fora da tela e sem tabulação --}}
    <div class="fale-armadilha" aria-hidden="true">
      <label for="site">Site</label>
      <input id="site" type="text" name="site" tabindex="-1" autocomplete="off">
    </div>

    <div class="fale-grade">
      <div class="field">
        <label for="nome">Nome</label>
        <input id="nome" class="input" type="text" name="nome" value="{{ old('nome') }}" required maxlength="200" autocomplete="name">
        @error('nome')<span class="field-error">{{ $message }}</span>@enderror
      </div>
      <div class="field">
        <label for="email">E-mail</label>
        <input id="email" class="input" type="email" name="email" value="{{ old('email') }}" required maxlength="150" autocomplete="email">
        @error('email')<span class="field-error">{{ $message }}</span>@enderror
      </div>
      <div class="field">
        <label for="telefone">Celular (opcional)</label>
        <input id="telefone" class="input" type="tel" name="telefone" value="{{ old('telefone') }}" maxlength="20" autocomplete="tel">
      </div>
      <div class="field">
        <label for="instituicao">Escola ou instituição (opcional)</label>
        <input id="instituicao" class="input" type="text" name="instituicao" value="{{ old('instituicao') }}" maxlength="200" autocomplete="organization">
      </div>
      <div class="field">
        <label for="cargo">Cargo (opcional)</label>
        <input id="cargo" class="input" type="text" name="cargo" value="{{ old('cargo') }}" maxlength="100">
      </div>
      <div class="field">
        <label for="assunto">Assunto</label>
        <select id="assunto" class="select" name="assunto" required>
          @foreach(['Conhecer o sistema', 'Contratar para minha escola ou rede', 'Sou professor e quero usar', 'Suporte', 'Outro assunto'] as $op)
            <option value="{{ $op }}" @selected(old('assunto') === $op)>{{ $op }}</option>
          @endforeach
        </select>
      </div>
    </div>

    <div class="field">
      <label for="mensagem">Mensagem</label>
      <textarea id="mensagem" class="input" name="mensagem" rows="6" required maxlength="2000">{{ old('mensagem') }}</textarea>
      @error('mensagem')<span class="field-error">{{ $message }}</span>@enderror
    </div>

    <p class="fale-aviso">Usamos seus dados só para responder a este contato. Não escreva dados de alunos nesta mensagem.</p>
    <button type="submit" class="btn btn-primary">Enviar mensagem</button>
  </form>
</div>
@endsection
