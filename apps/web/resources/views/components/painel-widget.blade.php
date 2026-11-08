@props(['id','titulo','secao','w'=>6,'h'=>9,'demo'=>false,'nivel'=>3])
@php($nivel=in_array((int)$nivel,[3,4],true)?(int)$nivel:3)
<article {{ $attributes->class(['card','painel-widget','painel-widget-kpi'=>str_starts_with($id,'kpi-')])->merge(['data-demo'=>$demo?'true':'false','data-widget-id'=>$id,'data-section'=>$secao,'data-default-w'=>(int)$w,'data-default-h'=>(int)$h]) }}>
  <header class="painel-widget-cabecalho">
    <div class="painel-widget-identificacao"><h{{ $nivel }} data-widget-titulo>{{ $titulo }}</h{{ $nivel }}>@if($demo)<div class="painel-widget-selos"><span class="badge badge-info">Em breve</span><span class="badge">Dados demonstrativos</span></div>@endif</div>
    @if($secao!=='resumo')
    <div class="painel-widget-controles">
      @if(in_array($id,['distribuicao-acertos','resultados-avaliacao','periodos-escolares','habilidades','reavaliacoes'],true))
      <button type="button" class="btn painel-widget-orientacao" data-grafico-orientacao aria-label="Barras horizontais" title="Barras horizontais"><span data-orientacao-horizontal aria-hidden="true"><x-icone nome="barras-horizontais" /></span><span data-orientacao-vertical hidden aria-hidden="true"><x-icone nome="barras-verticais" /></span></button>
      @endif
      <button type="button" class="btn painel-widget-opcao" data-widget-fixar hidden aria-pressed="false" aria-label="Fixar posição e tamanho de {{ $titulo }}" title="Fixar posição e tamanho"><x-icone nome="fixar" /></button>
      <button type="button" class="btn painel-widget-mover" data-widget-mover hidden aria-label="Mover {{ $titulo }}. Arraste ou use as setas para mover; Shift e setas ajustam o tamanho." title="Arraste para mover; setas movem e Shift+setas redimensionam"><x-icone nome="arrastar" /></button>
    </div>
    @endif
  </header>
  @if($demo)<span class="painel-valores-acessiveis">Exemplo ilustrativo; não representa os filtros aplicados.</span>@endif
  <div class="painel-widget-corpo">{{ $slot }}</div>
</article>
