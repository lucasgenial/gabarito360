@extends('layouts.public')
@section('title', 'Planos e preços — Gabarito360')
@section('description', 'Conheça os planos do Gabarito360 para professores, escolas e redes de ensino. Converse com a equipe sobre licenças e contratação.')
@section('content')
<section class="publico-abertura">
  <h1>Planos para sua rotina de avaliações</h1>
  <p>Do professor à rede de ensino. Conheça as opções e fale com a equipe para contratar.</p>
</section>
@if($indisponivel)
  <x-aviso tipo="info">Não foi possível consultar os preços agora. Fale com a equipe para conhecer as opções.</x-aviso>
@endif
<div class="plans-grid publico-planos">
  @foreach(array_merge($individuais, $institucional, $comerciais) as $p)
    <article class="plan-card">
      <h2 class="plan-name">{{ $p['nome'] }}</h2>
      <p class="plan-price">
        @if($p['modo'] === 'comercial' || ($p['preco_fixo'] === null && $p['preco_por_licenca'] === null))
          Sob consulta
        @elseif($p['preco_fixo'] !== null)
          R$ {{ number_format((float) $p['preco_fixo'], 2, ',', '.') }} <small>/{{ $p['periodicidade'] === 'anual' ? 'ano' : 'mês' }}</small>
        @else
          R$ {{ number_format((float) $p['preco_por_licenca'], 2, ',', '.') }} <small>/licença/mês</small>
        @endif
      </p>
      @if($p['periodicidade'] === 'anual' && $p['preco_fixo'] !== null)
        <p class="plan-desc">Equivalente a R$ {{ number_format((float) $p['preco_fixo'] / 12, 2, ',', '.') }} por mês, com cobrança anual.</p>
      @endif
      <ul class="plan-features">
        @if($p['licencas_min'] && $p['licencas_max'])
          <li>@if($p['licencas_min'] == $p['licencas_max']){{ $p['licencas_max'] }} {{ $p['licencas_max'] == 1 ? 'licença' : 'licenças' }}@else{{ $p['licencas_min'] }} a {{ $p['licencas_max'] }} licenças@endif</li>
        @endif
        @foreach(['escolas' => 'escola(s)', 'turmas' => 'turma(s)', 'provas_mes' => 'prova(s) por mês'] as $chave => $rotulo)
          @if(isset($p['limites'][$chave]))<li>Até {{ $p['limites'][$chave] }} {{ $rotulo }}</li>@endif
        @endforeach
        <li>Leitura de cartões-resposta pelo celular</li>
        <li>Relatórios de avaliações</li>
      </ul>
      <a class="btn btn-primary" href="{{ route('leads.index', ['plano_id' => $p['modo'] === 'comercial' ? $p['id'] : null]) }}">Falar sobre este plano</a>
    </article>
  @endforeach
</div>
<section class="publico-contato card card-pad">
  <div><h2>Precisa de uma proposta para sua escola ou rede?</h2><p>Conte o número de escolas e de pessoas que vão usar o sistema.</p></div>
  <a href="{{ route('leads.index') }}" class="btn btn-secondary">Fale conosco</a>
</section>
<section class="publico-faq" aria-labelledby="duvidas">
  <h2 id="duvidas">Dúvidas frequentes</h2>
  <details><summary>Como contratar?</summary><p>Envie uma mensagem com o plano de interesse. A equipe explica as condições e os próximos passos.</p></details>
  <details><summary>Posso conversar sobre uma escola ou uma rede inteira?</summary><p>Sim. Informe sua instituição e a quantidade estimada de pessoas que vão usar o sistema no Fale Conosco.</p></details>
  <details><summary>Já tenho conta. Onde entro?</summary><p>Acesse a <a href="{{ route('login') }}">página de entrada</a> com seu e-mail e sua senha.</p></details>
</section>
@endsection
