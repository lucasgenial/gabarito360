@php($painel=($meta['nome']??'')==='painel')
<div class="navegacao-regiao" role="status" aria-live="polite" aria-busy="true" data-navegacao-status>
  <p class="sr-only">Carregando conteúdo…</p>
  <div class="navegacao-esqueleto" aria-hidden="true">
    <div class="g360-skeleton navegacao-esqueleto-filtros"><i></i></div>
    @if($painel)<div class="navegacao-esqueleto-kpis">@for($i=0;$i<4;$i++)<div class="navegacao-esqueleto-bloco g360-skeleton"><i></i></div>@endfor</div>@endif
    <div @class(['navegacao-esqueleto-colunas'=>$painel])>
      <div class="navegacao-esqueleto-bloco g360-skeleton"><i></i><i></i><i></i><i></i></div>
      @if($painel)<div class="navegacao-esqueleto-bloco g360-skeleton"><i></i><i></i><i></i></div>@endif
    </div>
  </div>
</div>
