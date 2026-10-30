@props(['titulo' => null, 'acoes' => null])
{{-- Cartão: <x-cartao titulo="…"><x-slot:acoes>…</x-slot:acoes> conteúdo </x-cartao> --}}
<section {{ $attributes->merge(['class' => 'g-cartao']) }}>
  @if($titulo || $acoes)
    <div class="g-cartao-cab">
      @if($titulo)<h2>{{ $titulo }}</h2>@endif
      {{ $acoes }}
    </div>
  @endif
  <div class="g-cartao-corpo">{{ $slot }}</div>
</section>
