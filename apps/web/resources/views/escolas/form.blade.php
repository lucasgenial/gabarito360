@extends('layouts.app')

@section('tela', 'tela-escola-form')

{{--
  Arquivo: apps/web/resources/views/escolas/form.blade.php
  Função: Cadastro e edição de escola no modelo do ECOAR-MQA (docs/21, etapa 6).
          Cadastro: busca no Censo Escolar pelo código INEP ou pelo nome, que preenche o formulário;
          quem cadastra só confere e salva. Anexo = escola ligada a uma sede do mesmo núcleo.
          Diretor escolar edita os dados da própria escola (sem INEP, núcleo e vínculo).
--}}

@php
  $novo      = !$escola;
  $e         = $escola ?? [];
  $val       = fn ($campo, $padrao = null) => old($campo, $e[$campo] ?? $padrao);
  $gestor    = \App\Support\Acesso::pode('escola.criar');
  $marcados  = fn ($campo) => (array) old($campo, $e[$campo] ?? []);
  $tipoAtual = $val('tipo', request()->query('sede') ? 'anexo' : 'sede');
@endphp

@section('title', $novo ? 'Cadastrar escola' : 'Editar escola')

@section('breadcrumb')
<a href="{{ route('painel') }}">Início</a><span class="sep">/</span>
<a href="{{ route('escolas.index') }}">Escolas</a><span class="sep">/</span>
@if(!$novo)<a href="{{ route('escolas.show', $e['id']) }}">{{ $e['nome'] }}</a><span class="sep">/</span>@endif
<span>{{ $novo ? 'Cadastrar' : 'Editar' }}</span>
@endsection

@section('content')
<div style="margin-top:12px;">
  <h1 class="page-title">{{ $novo ? ($tipoAtual === 'anexo' ? 'Cadastrar anexo' : 'Cadastrar escola') : 'Editar escola' }}</h1>
  <p class="page-sub">Mesmos dados do Censo Escolar (INEP) usados no ECOAR. {{ $novo ? 'Digite o código INEP: os dados são preenchidos e você só confere.' : '' }}</p>
</div>

@if(session('erro'))<x-aviso tipo="erro">{{ session('erro') }}</x-aviso>@endif
@if($errors->any())<x-aviso tipo="erro">Confira os campos destacados abaixo.</x-aviso>@endif

<form method="POST" action="{{ $novo ? route('escolas.store') : route('escolas.update', $e['id']) }}" class="esc-form" id="esc-form">
  @csrf <x-revisao-acesso />
  @unless($novo) @method('PUT') @endunless

  {{-- 1. Censo Escolar --}}
  @if($gestor)
  <section class="sec">
    <div class="sec-head">
      <h2>1. Código INEP</h2>
      <p>Base do Censo Escolar. Não sabe o código? Busque pelo nome da escola (e o município, se quiser).</p>
    </div>
    <div class="sec-body">
      <div class="field">
        <label for="inep">Código INEP *</label>
        <div class="censo-busca">
          <input class="input" id="inep" name="inep" inputmode="numeric" maxlength="8" placeholder="8 dígitos" value="{{ $val('inep') === 'PENDENTE' ? '' : $val('inep') }}" required autocomplete="off">
          <button type="button" class="btn btn-secondary" id="btn-inep">Buscar no Censo</button>
        </div>
        @error('inep')<div class="erro-campo">{{ $message }}</div>@enderror
        @if(($e['inep'] ?? null) === 'PENDENTE')<div class="field-help">Esta escola está com o INEP pendente. Informe o código real para completar os dados pelo Censo.</div>@endif
      </div>
      <div id="censo-aviso"></div>
      <div class="field">
        <label for="censo-nome">Buscar pelo nome</label>
        <div class="censo-busca">
          <input class="input" id="censo-nome" placeholder="Ex.: Eraldo Tinoco Porto Seguro" autocomplete="off">
          <button type="button" class="btn btn-ghost" id="btn-nome">Procurar</button>
        </div>
        <ul class="censo-res" id="censo-res" hidden></ul>
      </div>
    </div>
  </section>
  @endif

  {{-- 2. Identificação --}}
  <section class="sec">
    <div class="sec-head"><h2>{{ $gestor ? '2.' : '1.' }} Identificação</h2></div>
    <div class="sec-body g2">
      <div class="field cheia">
        <label for="nome">Nome da escola *</label>
        <input class="input" id="nome" name="nome" maxlength="200" required value="{{ $val('nome') }}">
        @error('nome')<div class="erro-campo">{{ $message }}</div>@enderror
      </div>
      <div class="field">
        <label for="sigla">Sigla (opcional)</label>
        <input class="input" id="sigla" name="sigla" maxlength="40" value="{{ $val('sigla') }}" aria-describedby="sigla-ajuda">
        <div class="field-help" id="sigla-ajuda">Informe a sigla utilizada pela instituição. O nome completo será mantido.</div>
        @error('sigla')<div class="erro-campo">{{ $message }}</div>@enderror
      </div>
      @if($gestor)
        <div class="field">
          <label for="nucleo_id">Núcleo (NTE) *</label>
          <select class="select" id="nucleo_id" name="nucleo_id" required>
            <option value="">Selecione…</option>
            @foreach($nucleos as $n)
              <option value="{{ $n['id'] }}" data-orgao="{{ $n['orgao_regional'] ?? '' }}" @selected((string) $val('nucleo_id', count($nucleos) === 1 ? $n['id'] : null) === (string) $n['id'])>{{ $n['nome'] }}{{ !empty($n['orgao_regional']) ? ' · NTE ' . $n['orgao_regional'] : '' }}</option>
            @endforeach
          </select>
          @error('nucleo_id')<div class="erro-campo">{{ $message }}</div>@enderror
        </div>
        <div class="field">
          <label for="tipo">Vínculo *</label>
          <select class="select" id="tipo" name="tipo">
            <option value="sede" @selected($tipoAtual === 'sede')>Sede</option>
            <option value="anexo" @selected($tipoAtual === 'anexo')>Anexo de outra escola</option>
            <option value="unidade_vinculada" @selected($tipoAtual === 'unidade_vinculada')>Unidade vinculada</option>
          </select>
        </div>
        <div class="field cheia" id="campo-sede" @if($tipoAtual === 'sede') hidden @endif>
          <label for="escola_sede_id">Escola sede *</label>
          <select class="select" id="escola_sede_id" name="escola_sede_id">
            <option value="">Selecione a sede…</option>
            @foreach($sedes as $s)
              @continue(($e['id'] ?? null) === $s['id'])
              <option value="{{ $s['id'] }}" data-nucleo="{{ $s['nucleo_id'] }}" @selected((string) $val('escola_sede_id', request()->query('sede')) === (string) $s['id'])>{{ $s['nome'] }} ({{ $s['inep'] }})</option>
            @endforeach
          </select>
          <div class="field-help">O anexo fica no mesmo núcleo da sede.</div>
          @error('escola_sede_id')<div class="erro-campo">{{ $message }}</div>@enderror
        </div>
      @else
        <div class="field"><label>Código INEP</label><input class="input bloqueado" value="{{ $e['inep'] ?? '' }}" disabled></div>
        <div class="field"><label>Núcleo</label><input class="input bloqueado" value="{{ $e['nucleo_nome'] ?? '' }}" disabled></div>
      @endif
      <div class="field">
        <label for="tipo_rede">Esfera administrativa *</label>
        <select class="select" id="tipo_rede" name="tipo_rede" required>
          @foreach(['estadual' => 'Estadual', 'municipal' => 'Municipal', 'federal' => 'Federal', 'privada' => 'Privada'] as $k => $r)
            <option value="{{ $k }}" @selected($val('tipo_rede', 'estadual') === $k)>{{ $r }}</option>
          @endforeach
        </select>
      </div>
      <div class="field">
        <label for="situacao_funcionamento">Situação</label>
        <select class="select" id="situacao_funcionamento" name="situacao_funcionamento">
          <option value="">—</option>
          @foreach(['ativa' => 'Em atividade', 'paralisada' => 'Paralisada', 'extinta' => 'Extinta'] as $k => $r)
            <option value="{{ $k }}" @selected($val('situacao_funcionamento') === $k)>{{ $r }}</option>
          @endforeach
        </select>
      </div>
      <div class="field">
        <label for="localizacao">Localização</label>
        <select class="select" id="localizacao" name="localizacao">
          <option value="">—</option>
          <option value="urbana" @selected($val('localizacao') === 'urbana')>Urbana</option>
          <option value="rural" @selected($val('localizacao') === 'rural')>Rural</option>
        </select>
      </div>
      <input type="hidden" id="orgao_regional" name="orgao_regional" value="{{ $val('orgao_regional') }}">
    </div>
  </section>

  {{-- 3. Endereço e contato --}}
  <section class="sec">
    <div class="sec-head"><h2>{{ $gestor ? '3.' : '2.' }} Endereço e contato</h2></div>
    <div class="sec-body">
      <div class="g3">
        <div class="field"><label for="logradouro">Logradouro</label><input class="input" id="logradouro" name="logradouro" maxlength="255" value="{{ $val('logradouro') }}"></div>
        <div class="field"><label for="numero">Número</label><input class="input" id="numero" name="numero" maxlength="20" value="{{ $val('numero') }}"></div>
        <div class="field"><label for="cep">CEP</label><input class="input" id="cep" name="cep" inputmode="numeric" maxlength="9" placeholder="00000000" value="{{ $val('cep') }}">@error('cep')<div class="erro-campo">{{ $message }}</div>@enderror</div>
      </div>
      <div class="g2">
        <div class="field"><label for="complemento">Complemento</label><input class="input" id="complemento" name="complemento" maxlength="100" value="{{ $val('complemento') }}"></div>
        <div class="field"><label for="bairro">Bairro</label><input class="input" id="bairro" name="bairro" maxlength="100" value="{{ $val('bairro') }}"></div>
      </div>
      <div class="g3">
        <div class="field"><label for="cidade">Município</label><input class="input" id="cidade" name="cidade" maxlength="100" value="{{ $val('cidade') }}"></div>
        <div class="field"><label for="uf">UF</label><input class="input" id="uf" name="uf" maxlength="2" value="{{ $val('uf', 'BA') }}"></div>
        <div class="field"><label for="co_municipio">Cód. IBGE</label><input class="input" id="co_municipio" name="co_municipio" inputmode="numeric" maxlength="7" value="{{ $val('co_municipio') }}">@error('co_municipio')<div class="erro-campo">{{ $message }}</div>@enderror</div>
      </div>
      <div class="g2">
        <div class="field"><label for="telefone">Telefone</label><input class="input" id="telefone" name="telefone" maxlength="20" placeholder="(75) 0000-0000" value="{{ $val('telefone') }}"></div>
        <div class="field"><label for="email">E-mail</label><input class="input" id="email" type="email" name="email" maxlength="150" value="{{ $val('email') }}">@error('email')<div class="erro-campo">{{ $message }}</div>@enderror</div>
      </div>
      <input type="hidden" id="latitude" name="latitude" value="{{ $val('latitude') }}">
      <input type="hidden" id="longitude" name="longitude" value="{{ $val('longitude') }}">
    </div>
  </section>

  {{-- 4. Oferta --}}
  <section class="sec">
    <div class="sec-head"><h2>{{ $gestor ? '4.' : '3.' }} Oferta</h2><p>O Censo não traz estes dados: marque o que a escola oferece.</p></div>
    <div class="sec-body">
      @foreach(['turnos' => ['Turnos', $opcoes['turnos']], 'etapas' => ['Etapas de ensino', $opcoes['etapas']], 'modalidades' => ['Modalidades', $opcoes['modalidades']]] as $campo => [$rotulo, $lista])
        <div class="eyebrow" style="margin-bottom:6px;">{{ $rotulo }}</div>
        <div class="checks">
          @foreach($lista as $k => $r)
            <label><input type="checkbox" name="{{ $campo }}[]" value="{{ $k }}" @checked(in_array($k, $marcados($campo), true))> {{ $r }}</label>
          @endforeach
        </div>
      @endforeach
    </div>
  </section>

  <div class="acoes">
    <a class="btn btn-ghost" href="{{ $novo ? route('escolas.index') : route('escolas.show', $e['id']) }}">Cancelar</a>
    <button type="submit" class="btn btn-primary">{{ $novo ? 'Cadastrar escola' : 'Salvar alterações' }}</button>
  </div>
</form>
@endsection

@push('scripts')
@php($g360Config = ['id'=>$e['id']??null,'buscar'=>\App\Support\NavegacaoWeb::destino('escolas.censo.buscar'),'pesquisar'=>\App\Support\NavegacaoWeb::destino('escolas.censo.pesquisar')])
<script type="application/json" id="escola-formulario-config">@json($g360Config)</script>
<script src="{{ asset('js/escola-formulario.js') }}?v={{ filemtime(public_path('js/escola-formulario.js')) }}"></script>
@endpush
