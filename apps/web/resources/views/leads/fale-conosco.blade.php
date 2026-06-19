@extends('layouts.public')
@section('title', 'Fale Conosco — Gabarito360')

@push('styles')
<style>
.fale-wrap {
  max-width: 600px;
  margin: 2rem auto;
}
.fale-wrap h1 {
  font-size: 1.5rem;
  font-weight: 700;
  margin-bottom: .5rem;
}
.fale-wrap .subtitulo {
  color: var(--color-secondary-06);
  margin-bottom: 2rem;
  font-size: .95rem;
}
.plano-contexto {
  background: var(--color-primary-02);
  border: 1px solid var(--color-primary-03);
  border-radius: 6px;
  padding: .875rem 1rem;
  margin-bottom: 1.5rem;
  font-size: .875rem;
}
.plano-contexto strong { color: var(--color-primary-default); }
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
@media(max-width:540px) { .form-row { grid-template-columns: 1fr; } }
</style>
@endpush

@section('content')
<div class="fale-wrap">
  <h1>Fale Conosco</h1>
  <p class="subtitulo">Preencha o formulário e nossa equipe comercial entrará em contato para montar a proposta ideal para a sua rede.</p>

  @if($plano)
  <div class="plano-contexto">
    📋 Você está interessado no plano <strong>{{ $plano['nome'] }}</strong>.
    Deixe suas informações abaixo.
  </div>
  @endif

  <form method="POST" action="{{ route('leads.store') }}">
    @csrf

    @if($plano)
      <input type="hidden" name="plano_id" value="{{ $plano['id'] }}">
    @else
      <div class="form-group" style="margin-bottom:1rem">
        <label>Plano de interesse</label>
        <select name="plano_id" class="form-select">
          <option value="">Selecione (opcional)</option>
          @foreach($planos as $p)
            <option value="{{ $p['id'] }}">{{ $p['nome'] }}</option>
          @endforeach
        </select>
      </div>
    @endif

    <div class="form-row">
      <div class="form-group">
        <label>Nome completo <span style="color:red">*</span></label>
        <input type="text" name="nome" class="form-control" value="{{ old('nome') }}" required maxlength="200">
        @error('nome')<span class="field-error">{{ $message }}</span>@enderror
      </div>
      <div class="form-group">
        <label>E-mail <span style="color:red">*</span></label>
        <input type="email" name="email" class="form-control" value="{{ old('email') }}" required maxlength="150">
        @error('email')<span class="field-error">{{ $message }}</span>@enderror
      </div>
    </div>

    <div class="form-row" style="margin-top:1rem">
      <div class="form-group">
        <label>Telefone / WhatsApp</label>
        <input type="text" name="telefone" class="form-control" value="{{ old('telefone') }}" maxlength="20" placeholder="(99) 99999-9999">
      </div>
      <div class="form-group">
        <label>Cargo / Função</label>
        <input type="text" name="cargo" class="form-control" value="{{ old('cargo') }}" maxlength="100" placeholder="Ex.: Secretário de Educação">
      </div>
    </div>

    <div class="form-group" style="margin-top:1rem">
      <label>Instituição / Secretaria</label>
      <input type="text" name="instituicao" class="form-control" value="{{ old('instituicao') }}" maxlength="200" placeholder="Nome da sua escola, rede ou secretaria">
    </div>

    <div class="form-group" style="margin-top:1rem">
      <label>Estimativa de licenças (professores/usuários)</label>
      <input type="number" name="licencas_estimadas" class="form-control" value="{{ old('licencas_estimadas') }}" min="1" style="width:160px">
    </div>

    <div class="form-group" style="margin-top:1rem">
      <label>Mensagem / contexto adicional</label>
      <textarea name="mensagem" class="form-control" rows="4" maxlength="2000" placeholder="Conte um pouco sobre sua rede, quantas escolas tem, suas principais necessidades...">{{ old('mensagem') }}</textarea>
    </div>

    <div style="margin-top:1.5rem">
      <button type="submit" class="btn btn-primary">Enviar solicitação</button>
      <a href="{{ route('planos.index') }}" class="btn btn-outline" style="margin-left:.75rem">Voltar aos planos</a>
    </div>
  </form>
</div>
@endsection
