@props(['titulo', 'subtitulo' => null, 'chamada' => null])
{{-- Cabeçalho de página: <x-pagina titulo="…" subtitulo="…" chamada="…"> ações </x-pagina> --}}
<div class="g-pagina">
  <div style="min-width:0;">
    @if($chamada)<div class="eyebrow">{{ $chamada }}</div>@endif
    <h1 class="page-title">{{ $titulo }}</h1>
    @if($subtitulo)<p class="page-sub">{{ $subtitulo }}</p>@endif
  </div>
  @if(trim($slot) !== '')<div class="g-pagina-acoes">{{ $slot }}</div>@endif
</div>
