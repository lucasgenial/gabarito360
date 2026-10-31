{{--
  Arquivo: apps/web/resources/views/relatorios/_media-meta.blade.php
  Função: Média comparada à meta da rede: número, barra de 0 à nota máxima e marca da meta.
  Espera: $media, $notaMaxima, $meta (floats) e $titulo.
--}}
@php
  $maximo  = max((float) $notaMaxima, 0.1);
  $pct     = fn ($v) => min(100, max(0, round((float) $v / $maximo * 100, 1)));
  $fmtM    = fn ($v) => number_format((float) $v, 1, ',', '.');
  $atingiu = (float) $media >= (float) $meta;
@endphp
<h3 style="font-size:16px;margin-bottom:6px;">{{ $titulo }}</h3>
<div class="media-meta">
  <div class="media-meta-valor">{{ $fmtM($media) }} <small>de {{ $fmtM($maximo) }}</small></div>
  <div class="media-meta-trilha" role="img" aria-label="Média {{ $fmtM($media) }} de {{ $fmtM($maximo) }}, meta da rede {{ $fmtM($meta) }}">
    <i class="{{ $atingiu ? 'ok' : 'abaixo' }}" style="width:{{ $pct($media) }}%"></i>
    <b style="left:{{ $pct($meta) }}%"></b>
  </div>
  <div class="media-meta-legenda">Meta da rede: {{ $fmtM($meta) }}</div>
  <p class="{{ $atingiu ? 'media-meta-ok' : 'media-meta-abaixo' }}">{{ $atingiu ? 'Meta atingida' : 'Abaixo da meta' }}</p>
</div>
