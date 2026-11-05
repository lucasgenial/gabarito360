<p class="lp-sub">Da preparação à análise dos resultados, conheça o que você já pode conferir e a evolução do Gabarito360.</p>
<p class="publico-recursos-legenda">Recursos identificados com “Em breve” ainda não estão disponíveis.</p>
@foreach(config('apresentacao.momentos') as $momento)
  <section class="publico-momento" aria-labelledby="momento-{{ $loop->index }}">
    <div><h3 class="publico-momento-titulo" id="momento-{{ $loop->index }}">{{ $momento['nome'] }}</h3><p>{{ $momento['beneficio'] }}</p></div>
    <div class="publico-momento-lista">
      @foreach($momento['recursos'] as $recurso)
        <article class="publico-recurso" @if($recurso['em_breve'] ?? false) data-recurso-futuro @endif>
          @if($recurso['em_breve'] ?? false)<span class="publico-em-breve">Em breve</span>@endif
          <h4>{{ $recurso['nome'] }}</h4><p>{{ $recurso['descricao'] }}</p>
          @if(!empty($recurso['ajuda']))<details class="publico-ajuda"><summary>Saiba mais</summary><p>{{ $recurso['ajuda'] }}</p></details>@endif
        </article>
      @endforeach
    </div>
  </section>
@endforeach
