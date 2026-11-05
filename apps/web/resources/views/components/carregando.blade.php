@props(['rotulo'=>'Carregando…','formato'=>'linhas'])
<div {{ $attributes->class(['g360-carregando','g360-carregando-'.$formato]) }}>
  <div class="g360-carga-marca"><span aria-hidden="true"><x-marca variant="icon" style="--marca-icone:40px" /></span><span>{{ $rotulo }}</span></div>
  <div class="g360-skeleton" aria-hidden="true"><i></i><i></i><i></i></div>
</div>
