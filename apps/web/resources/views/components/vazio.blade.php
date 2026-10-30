@props(['titulo', 'icone' => 'prova'])
{{-- Estado vazio: <x-vazio titulo="Nenhuma prova" icone="prova">explicação</x-vazio> --}}
<div class="g-vazio">
  <x-icone :nome="$icone" />
  <b>{{ $titulo }}</b>
  {{ $slot }}
</div>
