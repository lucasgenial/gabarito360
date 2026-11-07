@props(['id','titulo'])
<section class="painel-secao" data-painel-secao="{{ $id }}" aria-label="{{ $titulo }}">
  <h2>{{ $titulo }}</h2>
  <div class="painel-secao-grade">{{ $slot }}</div>
</section>
