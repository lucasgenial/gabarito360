<p class="lp-sub">Da preparação à devolutiva, conheça os recursos atuais e a evolução do Gabarito360.</p>
<p class="publico-recursos-legenda">O selo “Em breve” identifica recursos que ainda não estão disponíveis.</p>
<div class="publico-momentos">
@foreach(config('apresentacao.momentos') as $momento)
  <section class="publico-momento" aria-labelledby="momento-{{ $loop->index }}">
    <header class="publico-momento-cabecalho"><h3 id="momento-{{ $loop->index }}">{{ $momento['nome'] }}</h3><p>{{ $momento['beneficio'] }}</p></header>
    @foreach($momento['recursos'] as $recurso)
      @if(!($recurso['em_breve'] ?? false))
        <article class="publico-recurso publico-recurso-atual" data-recurso-atual>
          <div><span class="publico-recurso-estado">Recurso atual</span><h4>{{ $recurso['nome'] }}</h4></div>
          <div><p>{{ $recurso['descricao'] }}</p>@if(!empty($recurso['ajuda']))<details class="publico-ajuda"><summary>Saiba mais</summary><p>{{ $recurso['ajuda'] }}</p></details>@endif</div>
        </article>
      @endif
    @endforeach
    <div class="publico-momento-lista">
      @foreach($momento['recursos'] as $recurso)
        @if($recurso['em_breve'] ?? false)
          <article class="publico-recurso publico-recurso-futuro" data-recurso-futuro>
            <div class="publico-recurso-titulo"><h4>{{ $recurso['nome'] }}</h4><span class="publico-em-breve">Em breve</span></div>
            <p>{{ $recurso['descricao'] }}</p>
            @if(!empty($recurso['ajuda']))<details class="publico-ajuda"><summary>Saiba mais</summary><p>{{ $recurso['ajuda'] }}</p></details>@endif
          </article>
        @endif
      @endforeach
    </div>
  </section>
@endforeach
</div>
