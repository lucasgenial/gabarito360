{{--
  Arquivo: apps/web/resources/views/relatorios/_distribuicao-notas.blade.php
  Função: Histograma das notas (10 faixas de 0 à nota máxima) para os relatórios.
  Espera: $alunos = [{nota_final}], $notaMaxima (float).
--}}
@php
  $max    = max((float) $notaMaxima, 0.1);
  $faixas = array_fill(0, 10, 0);
  foreach ($alunos as $a) {
      $i = min(9, (int) floor(((float) $a['nota_final']) / $max * 10));
      $faixas[max(0, $i)]++;
  }
  $passo = $max / 10;
  $fmtF  = fn ($n) => rtrim(rtrim(number_format($n, 1, ',', ''), '0'), ',');
@endphp
@php
  $rotulos = [];
  $cores   = [];
  foreach ($faixas as $i => $qtd) {
      $rotulos[] = $fmtF($i * $passo) . '–' . $fmtF(($i + 1) * $passo);
      $cores[]   = ($i + 1) * $passo <= $max * 0.6 - 0.0001 ? '--danger' : '--serie-1';
  }
@endphp
<h3 style="font-size:16px;margin-bottom:6px;">Distribuição das notas</h3>
@if(count($alunos))
  <x-grafico descricao="Quantidade de alunos por faixa de nota" :altura="230"
    :dados="['categorias' => $rotulos, 'inteiros' => true, 'rotulos' => true, 'intervalo' => 0,
             'series' => [['nome' => 'Alunos', 'valores' => $faixas, 'cores' => $cores]]]">
    <table class="table">
      <thead><tr><th>Faixa de nota</th><th>Alunos</th></tr></thead>
      <tbody>
        @foreach($faixas as $i => $qtd)
          <tr><td>{{ $rotulos[$i] }}</td><td class="num">{{ $qtd }}</td></tr>
        @endforeach
      </tbody>
    </table>
  </x-grafico>
  <p class="field-help">Alunos por faixa de nota. Em vermelho, abaixo de 60% da nota máxima.</p>
@else
  <p class="field-help">Sem notas ainda.</p>
@endif
