@props(['dados', 'descricao', 'altura' => 240])
{{-- Gráfico ECharts: <x-grafico :dados="[...]" descricao="…"> tabela com os mesmos números </x-grafico>
     Só no HTML: componente Blade dentro de JavaScript quebra o script da tela. --}}
<div class="grafico" role="img" aria-label="{{ $descricao }}" style="height:{{ (int) $altura }}px;" data-grafico="{{ json_encode($dados, JSON_UNESCAPED_UNICODE) }}"></div>
@if(trim($slot) !== '')
  <details class="grafico-dados">
    <summary>Ver os números</summary>
    <div class="table-wrap">{{ $slot }}</div>
  </details>
@endif
@once
  @push('scripts')
    <script src="{{ asset('js/vendor/echarts-6.1.0.common.min.js') }}"></script>
    <script src="{{ asset('js/graficos.js') }}?v={{ @filemtime(public_path('js/graficos.js')) }}"></script>
  @endpush
@endonce
