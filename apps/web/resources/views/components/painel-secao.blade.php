@props(['id','titulo','nivel'=>2])
@php($nivel=in_array((int)$nivel,[2,3],true)?(int)$nivel:2)
<section class="painel-secao" data-painel-secao="{{ $id }}" aria-label="{{ $titulo }}">
  <h{{ $nivel }}>{{ $titulo }}</h{{ $nivel }}>
  <div class="painel-secao-grade">{{ $slot }}</div>
</section>
