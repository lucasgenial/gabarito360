@props(['id','titulo','secao','w'=>6,'h'=>9,'demo'=>false])
<article {{ $attributes->class(['card','painel-widget','painel-widget-kpi'=>str_starts_with($id,'kpi-')])->merge(['data-demo'=>$demo?'true':'false','data-widget-id'=>$id,'data-section'=>$secao,'data-default-w'=>(int)$w,'data-default-h'=>(int)$h]) }}>
  <header class="painel-widget-cabecalho"><h3>{{ $titulo }}</h3>@if($secao!=='resumo')<button type="button" class="btn btn-ghost painel-widget-mover" data-widget-mover hidden aria-label="Mover {{ $titulo }}. Setas mudam a posição; Shift e setas ajustam o tamanho." title="Arraste para mover; setas movem e Shift+setas redimensionam"><x-icone nome="transferir" /></button>@endif</header>
  @if($demo)<div class="painel-demo-avisos"><span class="badge badge-info">Em breve</span><strong>Dados demonstrativos</strong><p>Exemplo ilustrativo; não representa os filtros aplicados.</p></div>@endif
  <div class="painel-widget-corpo">{{ $slot }}</div>
</article>
