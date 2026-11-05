@props(['rotulo'=>'Carregando…','formato'=>'linhas'])
<div {{ $attributes->class(['g360-carregando','g360-carregando-'.$formato]) }}>
  <div class="g360-carga-marca"><img src="{{ asset('images/g360-carregando.webp') }}" width="40" height="40" alt="" aria-hidden="true"><span>{{ $rotulo }}</span></div>
  <div class="g360-skeleton" aria-hidden="true"><i></i><i></i><i></i></div>
</div>
