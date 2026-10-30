{{--
  Arquivo: apps/web/resources/views/relatorios/_distribuicao-notas.blade.php
  Função: Histograma das notas (10 faixas de 0 à nota máxima) em SVG, para os relatórios.
  Espera: $alunos = [{nota_final}], $notaMaxima (float).
--}}
@php
  $max    = max((float) $notaMaxima, 0.1);
  $faixas = array_fill(0, 10, 0);
  foreach ($alunos as $a) {
      $i = min(9, (int) floor(((float) $a['nota_final']) / $max * 10));
      $faixas[max(0, $i)]++;
  }
  $maior = max(1, max($faixas));
  $passo = $max / 10;
  $fmtF  = fn ($n) => rtrim(rtrim(number_format($n, 1, ',', ''), '0'), ',');
@endphp
<h3 style="font-size:16px;margin-bottom:6px;">Distribuição das notas</h3>
@if(count($alunos))
  <svg viewBox="0 0 420 170" width="100%" role="img" aria-label="Quantidade de alunos por faixa de nota">
    @foreach($faixas as $i => $qtd)
      @php
        $h = round($qtd / $maior * 120);
        $x = 12 + $i * 40;
        $cor = ($i + 1) * $passo <= $max * 0.6 - 0.0001 ? '#e52207' : '#168821';
      @endphp
      <rect x="{{ $x }}" y="{{ 132 - $h }}" width="32" height="{{ max($h, 1) }}" rx="3" fill="{{ $cor }}" opacity="{{ $qtd ? 0.85 : 0.15 }}"></rect>
      @if($qtd)<text x="{{ $x + 16 }}" y="{{ 128 - $h }}" text-anchor="middle" font-size="11" font-weight="700" fill="currentColor">{{ $qtd }}</text>@endif
      <text x="{{ $x + 16 }}" y="148" text-anchor="middle" font-size="9" fill="#888">{{ $fmtF($i * $passo) }}–{{ $fmtF(($i + 1) * $passo) }}</text>
    @endforeach
    <text x="12" y="165" font-size="9" fill="#888">alunos por faixa de nota · vermelho = abaixo de 60% da nota máxima</text>
  </svg>
@else
  <p class="field-help">Sem notas ainda.</p>
@endif
