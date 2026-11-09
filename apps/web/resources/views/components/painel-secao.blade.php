@props(['id','titulo','nivel'=>2])
@php($nivel=in_array((int)$nivel,[2,3],true)?(int)$nivel:2)
<section class="painel-secao" data-painel-secao="{{ $id }}" aria-label="{{ $titulo }}">
  <header class="painel-secao-cabecalho"><h{{ $nivel }}>{{ $titulo }}</h{{ $nivel }}>
    @if($id!=='resumo')<x-painel-mover-grupo tipo="secao" :titulo="$titulo" />@endif
  </header>
  <div class="painel-secao-grade">{{ $slot }}</div>
</section>
