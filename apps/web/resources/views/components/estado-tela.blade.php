@props(['titulo', 'mensagem'=>null, 'carregando'=>false, 'codigo'=>null])
<section {{ $attributes->class(['estado-conteudo','estado-conteudo--carregando'=>(bool)$carregando]) }} @if($carregando) role="status" aria-live="polite" aria-busy="true" @endif>
  <div class="estado-marca" aria-hidden="true"><x-marca variant="icon" size="lg" /></div>
  @if($codigo)<p class="estado-codigo">{{ $codigo }}</p>@endif
  <h1>{{ $titulo }}</h1>
  @if($mensagem)<p class="estado-mensagem">{{ $mensagem }}</p>@endif
  @if($slot->isNotEmpty())<div class="estado-acoes">{{ $slot }}</div>@endif
</section>
