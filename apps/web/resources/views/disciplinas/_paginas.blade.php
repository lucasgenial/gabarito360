<nav class="dc1-paginas" aria-label="{{ $rotulo ?? 'Páginas das disciplinas' }}">
  <span>{{ $meta['total'] ?? 0 }} resultado(s) · Página {{ $meta['pagina'] ?? 1 }} de {{ $meta['paginas'] ?? 1 }}</span>
  @if(($meta['pagina']??1)>1)<a class="btn btn-secondary" href="{{ route($rota,array_replace($parametros??[],['page'=>$meta['pagina']-1])) }}">Anterior</a>@endif
  @if(($meta['pagina']??1)<($meta['paginas']??1))<a class="btn btn-secondary" href="{{ route($rota,array_replace($parametros??[],['page'=>$meta['pagina']+1])) }}">Próxima</a>@endif
</nav>
