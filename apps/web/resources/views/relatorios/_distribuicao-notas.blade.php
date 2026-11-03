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
    <x-tabela titulo="Distribuição das notas" modo="lista"><table class="tabela-padrao table"><caption class="sr-only">Distribuição das notas</caption>
      <thead><tr><th scope="col">Faixa de nota</th><th scope="col">Alunos</th></tr></thead>
      <tbody>
        @foreach($faixas as $i => $qtd)
          <tr><td class="tabela-principal" data-rotulo="Faixa de nota"><div class="tabela-valor">{{ $rotulos[$i] }}</div></td><td class="num" data-rotulo="Alunos"><div class="tabela-valor">{{ $qtd }}</div></td></tr>
        @endforeach
      </tbody>
    </table></x-tabela>
  </x-grafico>
  <p class="field-help">Alunos por faixa de nota. Em vermelho, abaixo de 60% da nota máxima.</p>
@else
  <p class="field-help">Sem notas ainda.</p>
@endif
