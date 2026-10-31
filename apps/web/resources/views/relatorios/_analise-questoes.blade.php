{{--
  Arquivo: apps/web/resources/views/relatorios/_analise-questoes.blade.php
  Função: Análise por questão — % de acerto (gráfico), questões mais difíceis e a
          distribuição das marcações por alternativa, com o distrator mais marcado.
  Espera: $questoes = [{numero, gabarito, gabarito_espanhol, anulada, total, acertos, pct,
          distribuicao{A..E,branco}, distrator}]
--}}
@php
  $comDados = collect($questoes)->filter(fn ($q) => $q['pct'] !== null && !$q['anulada']);
  $dificeis = $comDados->sortBy('pct')->take(5)->values();
  $faceis   = $comDados->sortByDesc('pct')->take(5)->values();
  $nivel    = fn ($p) => $p === null ? ['#bbb', '—'] : ($p >= 70 ? ['#168821', 'fácil'] : ($p >= 40 ? ['#c27c0e', 'média'] : ['#e52207', 'difícil']));
  $n        = count($questoes);
  $larg     = max(560, $n * 16 + 40);
@endphp

<div class="card card-pad" style="margin-top:20px;">
  <div class="row-between" style="flex-wrap:wrap;">
    <h3 style="font-size:17px;">Análise por questão</h3>
    <span style="font-size:12px;color:var(--muted);">% de alunos que acertaram · <span style="color:var(--success);">■</span> fácil (≥70%) <span style="color:#c27c0e;">■</span> média <span style="color:var(--danger);">■</span> difícil (&lt;40%)</span>
  </div>

  @if($comDados->isEmpty())
    <p class="field-help" style="margin-top:8px;">A análise aparece quando houver cartões lidos.</p>
  @else
    <div style="overflow-x:auto;margin-top:10px;">
      <svg viewBox="0 0 {{ $larg }} 170" width="100%" style="min-width:{{ min($larg, 720) }}px;" role="img" aria-label="Percentual de acerto por questão">
        @foreach([0, 50, 100] as $g)
          <line x1="28" x2="{{ $larg - 6 }}" y1="{{ 130 - $g * 1.1 }}" y2="{{ 130 - $g * 1.1 }}" stroke="#ddd" stroke-dasharray="{{ $g ? '3 3' : '' }}"></line>
          <text x="24" y="{{ 133 - $g * 1.1 }}" text-anchor="end" font-size="9" fill="#888">{{ $g }}%</text>
        @endforeach
        @foreach($questoes as $i => $q)
          @php [$cor] = $nivel($q['anulada'] ? null : $q['pct']); $h = ($q['pct'] ?? 0) * 1.1; $x = 32 + $i * (($larg - 40) / max($n, 1)); $w = max(6, ($larg - 40) / max($n, 1) - 4); @endphp
          <rect x="{{ round($x, 1) }}" y="{{ 130 - $h }}" width="{{ round($w, 1) }}" height="{{ max($h, 1) }}" rx="2" fill="{{ $cor }}">
            <title>Questão {{ $q['numero'] }}: {{ $q['anulada'] ? 'anulada' : ($q['pct'] ?? 0) . '% de acerto (' . $q['acertos'] . '/' . $q['total'] . ')' }}</title>
          </rect>
          <text x="{{ round($x + $w / 2, 1) }}" y="145" text-anchor="middle" font-size="8" fill="#666">{{ $q['numero'] }}</text>
        @endforeach
      </svg>
    </div>

    <div class="aq-listas">
      <div>
        <div class="eyebrow" style="color:var(--danger);">Mais difíceis — revisar em aula</div>
        <ul style="list-style:none;padding:0;margin:6px 0 0;">
          @foreach($dificeis as $q)
            <li><span><b>Questão {{ str_pad($q['numero'], 2, '0', STR_PAD_LEFT) }}</b> · certa {{ $q['gabarito'] }}@if($q['distrator']) · mais marcada errada: <b>{{ $q['distrator'] }}</b>@endif</span><b style="color:var(--danger);">{{ $q['pct'] }}%</b></li>
          @endforeach
        </ul>
      </div>
      <div>
        <div class="eyebrow" style="color:var(--success);">Mais acertadas</div>
        <ul style="list-style:none;padding:0;margin:6px 0 0;">
          @foreach($faceis as $q)
            <li><span><b>Questão {{ str_pad($q['numero'], 2, '0', STR_PAD_LEFT) }}</b> · certa {{ $q['gabarito'] }}</span><b style="color:var(--success);">{{ $q['pct'] }}%</b></li>
          @endforeach
        </ul>
      </div>
    </div>

    <details class="aq-detalhe" style="margin-top:14px;">
      <summary style="cursor:pointer;font-weight:600;font-size:14px;">Ver marcações de cada questão (A–E)</summary>
      @php $letras = array_keys(collect($questoes)->first()['distribuicao'] ?? []); @endphp
      <div class="quadro-cel" style="overflow-x:auto;">
        <table class="aq-tabela">
          <thead><tr><th class="fixa">Questão</th>@foreach($letras as $l)<th>{{ $l === 'branco' ? 'Branco' : $l }}</th>@endforeach<th>Acerto</th></tr></thead>
          <tbody>
            @foreach($questoes as $q)
              <tr>
                <td class="fixa"><b>{{ str_pad($q['numero'], 2, '0', STR_PAD_LEFT) }}</b>@if($q['gabarito_espanhol']) <span style="font-size:10px;color:var(--muted);">ING {{ $q['gabarito'] }} · ESP {{ $q['gabarito_espanhol'] }}</span>@endif</td>
                @foreach($letras as $l)
                  <td><span class="{{ $l === $q['gabarito'] ? 'aq-certa' : ($l === $q['distrator'] ? 'aq-distr' : '') }}" style="padding:1px 6px;">{{ $q['distribuicao'][$l] ?? 0 }}</span></td>
                @endforeach
                <td><b style="color:{{ $nivel($q['anulada'] ? null : $q['pct'])[0] }};">{{ $q['anulada'] ? 'anulada' : ($q['pct'] !== null ? $q['pct'] . '%' : '—') }}</b></td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
      <p style="font-size:12px;color:var(--muted);margin-top:6px;">Verde = alternativa correta · vermelho = alternativa errada mais marcada (distrator).</p>
    </details>
  @endif
</div>
