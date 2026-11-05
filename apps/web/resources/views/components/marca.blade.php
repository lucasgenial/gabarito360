@props(['variant'=>'horizontal','size'=>'md','theme'=>'auto','animated'=>false])
@php
  $variant=in_array($variant,['horizontal','icon'],true)?$variant:'horizontal';
  $size=in_array($size,['sm','md','lg'],true)?$size:'md';
  $theme=in_array($theme,['auto','light','dark'],true)?$theme:'auto';
  $animated=filter_var($animated,FILTER_VALIDATE_BOOLEAN);
@endphp
<span {{ $attributes->class(['g360-marca','g360-marca-'.$size,'g360-marca-'.$variant,'g360-marca-animada'=>(bool)$animated]) }} data-marca-theme="{{ $theme }}" role="img" aria-label="Gabarito360">
  <span class="g360-simbolo" aria-hidden="true">{!! file_get_contents(resource_path('brand/gabarito360-icon.svg')) !!}</span>
  @if($variant==='horizontal')<span class="g360-wordmark" aria-hidden="true">Gabarito<span class="g360-numeros">3<span class="g360-six">6</span>0</span></span>@endif
</span>
