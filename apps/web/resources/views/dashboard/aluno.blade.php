@extends('layouts.app')

@section('title', 'Painel do Aluno')

@push('styles')
<style>
  .welcome-card {
    display:grid; grid-template-columns:64px 1fr; gap:18px; align-items:center;
    margin-top:18px; padding:22px 24px;
    background:linear-gradient(135deg, var(--accent-light), #eef4fb);
    border:1px solid #b9cce8; border-radius:var(--radius-lg); box-shadow:var(--shadow-sm);
  }
  .welcome-avatar {
    width:64px; height:64px; border-radius:50%; display:grid; place-items:center;
    background:var(--accent); color:#fff; font-size:24px; font-weight:800;
  }
  .welcome-card p { color:var(--fg-2); margin-top:4px; }
  .kpi-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:18px; margin-top:24px; }
  .main-grid { display:grid; grid-template-columns:1.7fr 1fr; gap:20px; margin-top:20px; }
  .side-stack { display:grid; gap:20px; }
  .section-title { font-size:17px; margin-bottom:14px; font-weight:700; }
  .subject-badge { min-width:92px; justify-content:center; }
  .trend-cell { font-weight:700; font-size:16px; }
  .trend-up { color:var(--success,#168821); }
  .trend-down { color:var(--danger,#e52207); }
  .trend-flat { color:var(--warn-fg,#b06800); }
  .bar-chart { display:flex; align-items:flex-end; gap:10px; height:80px; margin-bottom:6px; }
  .bar-wrap { flex:1; display:flex; flex-direction:column; align-items:center; gap:6px; }
  .bar { width:100%; border-radius:6px 6px 0 0; background:var(--surface-2); transition:height .3s; }
  .bar.active { background:var(--accent); }
  .bar-label { font-size:11px; color:var(--muted); text-align:center; }
  .bar-val { font-size:12px; font-weight:700; color:var(--accent-dark); }
  .waiting-grid { display:grid; grid-template-columns:repeat(2,1fr); gap:12px; margin-top:16px; }
  .waiting-card {
    padding:14px; border:1px dashed var(--border); border-radius:var(--radius-md);
    background:var(--surface-2);
  }
  .exam-list { list-style:none; padding:0; margin:0; }
  .exam-list li {
    display:flex; align-items:center; justify-content:space-between; gap:12px;
    padding:14px 16px; background:var(--surface);
    border:1px solid var(--border-soft); border-radius:var(--radius-md);
  }
  .exam-list li + li { margin-top:10px; }
  .exam-meta b { display:block; font-size:14px; }
  .exam-meta span { color:var(--muted); font-size:13px; }
  .motivation-card {
    padding:18px; background:var(--accent-light);
    border:1px solid #b5c9e8; border-left:5px solid var(--accent);
    border-radius:var(--radius-md); color:var(--accent-dark);
  }
  @media (max-width:1080px){ .kpi-grid{grid-template-columns:repeat(2,1fr);} .main-grid{grid-template-columns:1fr;} }
  @media (max-width:640px){ .welcome-card,.kpi-grid,.waiting-grid{grid-template-columns:1fr;} }
</style>
@endpush

@section('nav')
<a href="{{ route('painel') }}" class="active">Painel</a>
<a href="{{ route('provas.index') }}">Minhas Provas</a>
@endsection

@section('context-badge')
@php $turmaAluno = $dashboard['aluno']['turma'] ?? null; @endphp
@if($turmaAluno)
  <span class="badge badge-info badge-dot">{{ $turmaAluno }}</span>
@endif
@endsection

@section('breadcrumb')
<span>Início</span><span class="sep">/</span><span>Painel</span>
@endsection

@section('content')

@php
  $aluno          = $dashboard['aluno']           ?? null;
  $kpis           = $dashboard['kpis']            ?? null;
  $notas          = $dashboard['notas']           ?? [];
  $bimestres      = $dashboard['bimestres']       ?? [];
  $proximasProvas = $dashboard['proximas_provas'] ?? [];

  $nomeAluno    = $aluno['nome']   ?? $nome;
  $turma        = $aluno['turma']  ?? '';
  $escola       = $aluno['escola'] ?? '';
  $primeiroNome = explode(' ', $nomeAluno)[0];

  $iniciais = implode('', array_map(
    fn($p) => strtoupper($p[0]),
    array_slice(array_filter(explode(' ', $nomeAluno)), 0, 2)
  ));

  // Badge da média
  $media     = $kpis['minha_media'] ?? null;
  $mediaBadge = match(true) {
    $media === null        => ['cls' => 'badge-muted',    'txt' => 'sem dados'],
    $media >= 8            => ['cls' => 'badge-success',  'txt' => 'bom desempenho'],
    $media >= 6            => ['cls' => 'badge-info',     'txt' => 'desempenho regular'],
    default                => ['cls' => 'badge-danger',   'txt' => 'precisa melhorar'],
  };

  // Próxima prova formatada
  $proxData = $kpis['proxima_prova_data'] ?? null;
  $proxDisc = $kpis['proxima_prova_disc'] ?? null;
  $proxLabel = $proxData
    ? \Carbon\Carbon::parse($proxData)->isoFormat('ddd[.] DD/MM')
    : '—';

  // Bimestre atual
  $bimAtual = (int) ceil(now()->month / 3);

  // Máximo das médias para escalar barras
  $maxMedia = collect($bimestres)->pluck('media')->filter()->max() ?: 10;
@endphp

{{-- Card de boas-vindas --}}
<div class="welcome-card">
  <div class="welcome-avatar" aria-hidden="true">{{ $iniciais }}</div>
  <div>
    <div class="eyebrow">Seu acompanhamento</div>
    <h1 class="page-title" style="margin-bottom:0;">Olá, {{ $primeiroNome }}!</h1>
    <p>{{ $nomeAluno }} ({{ $iniciais }}) · Aluno · {{ $turma }}{{ $escola ? ' · '.$escola : '' }}</p>
  </div>
</div>

{{-- KPIs --}}
<div class="kpi-grid">
  <div class="card kpi">
    <div class="kpi-label">Provas realizadas</div>
    <div class="kpi-value">{{ $kpis['provas_realizadas'] ?? '—' }}</div>
    <div class="kpi-trend up">▲ no período letivo</div>
  </div>

  <div class="card kpi">
    <div class="kpi-label">Minha média</div>
    <div class="kpi-value">
      {{ $media !== null ? number_format($media, 1, ',', '') : '—' }}
    </div>
    <div class="kpi-trend">
      <span class="badge {{ $mediaBadge['cls'] }}">{{ $mediaBadge['txt'] }}</span>
    </div>
  </div>

  <div class="card kpi">
    <div class="kpi-label">Melhor disciplina</div>
    <div class="kpi-value" style="font-size:24px;">
      {{ $kpis['melhor_disciplina'] ?? '—' }}
    </div>
    <div class="kpi-trend up">
      @if($kpis['melhor_media'] ?? null)
        ▲ {{ number_format($kpis['melhor_media'], 1, ',', '') }} na última prova
      @else
        ● sem avaliações
      @endif
    </div>
  </div>

  <div class="card kpi">
    <div class="kpi-label">Próxima prova</div>
    <div class="kpi-value" style="font-size:24px;">{{ $proxLabel }}</div>
    <div class="kpi-trend">
      @if($proxDisc)
        ● {{ $proxDisc }}
      @else
        ● nenhuma agendada
      @endif
    </div>
  </div>
</div>

{{-- Grade principal --}}
<div class="main-grid">

  {{-- Tabela de notas --}}
  <div class="card card-pad">
    <div class="row-between" style="margin-bottom:12px;">
      <h2 class="section-title" style="margin-bottom:0;">Minhas notas</h2>
      <span class="badge badge-muted">{{ $bimAtual }}º bimestre</span>
    </div>
    <table class="table">
      <thead>
        <tr>
          <th>Disciplina</th>
          <th>Última prova</th>
          <th>Nota</th>
          <th>Média bimestral</th>
          <th>Tendência</th>
        </tr>
      </thead>
      <tbody>
        @forelse($notas as $n)
          @php
            $tendCls = match($n['tendencia'] ?? 'flat') {
              'up'   => 'trend-up',
              'down' => 'trend-down',
              default => 'trend-flat',
            };
            $tendSym = match($n['tendencia'] ?? 'flat') {
              'up'   => '↑',
              'down' => '↓',
              default => '→',
            };
          @endphp
          <tr>
            <td>{{ $n['disciplina'] }}</td>
            <td class="num">
              {{ $n['ultima_data'] ? \Carbon\Carbon::parse($n['ultima_data'])->format('d/m/Y') : '—' }}
            </td>
            <td class="num">
              {{ $n['ultima_nota'] !== null ? number_format($n['ultima_nota'], 1, ',', '') : '—' }}
            </td>
            <td class="num">
              {{ $n['media_bimestral'] !== null ? number_format($n['media_bimestral'], 1, ',', '') : '—' }}
            </td>
            <td><span class="trend-cell {{ $tendCls }}">{{ $tendSym }}</span></td>
          </tr>
        @empty
          <tr>
            <td colspan="5" style="text-align:center;color:var(--muted);padding:24px;">
              Nenhuma nota registrada ainda.
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  {{-- Coluna lateral --}}
  <div class="side-stack">

    {{-- Evolução bimestral --}}
    <div class="card card-pad">
      <h2 class="section-title">Evolução bimestral</h2>

      @php
        $bimComDados    = collect($bimestres)->filter(fn($b) => $b['media'] !== null);
        $bimSemDados    = collect($bimestres)->filter(fn($b) => $b['media'] === null);
      @endphp

      @if($bimComDados->count())
        <div class="bar-chart" role="img" aria-label="Gráfico de evolução bimestral">
          @foreach($bimComDados as $b)
            @php
              $pct = $maxMedia > 0 ? round(($b['media'] / $maxMedia) * 100) : 0;
              $isCurrent = $b['bimestre'] == $bimAtual;
            @endphp
            <div class="bar-wrap">
              <span class="bar-val">{{ number_format($b['media'], 1, ',', '') }}</span>
              <div class="bar {{ $isCurrent ? 'active' : '' }}"
                   style="height:{{ max(8, $pct) }}%;"
                   title="{{ $b['label'] }}: {{ number_format($b['media'], 1, ',', '') }}">
              </div>
              <span class="bar-label">{{ $b['label'] }}</span>
            </div>
          @endforeach
        </div>
      @else
        <p style="color:var(--muted);font-size:13px;text-align:center;padding:12px 0;">
          Sem médias bimestrais ainda.
        </p>
      @endif

      @if($bimSemDados->count())
        <div class="waiting-grid">
          @foreach($bimSemDados as $b)
            <div class="waiting-card">
              <div class="eyebrow" style="margin-bottom:6px;">{{ $b['label'] }}</div>
              <div class="page-sub">Aguardando avaliações</div>
            </div>
          @endforeach
        </div>
      @endif
    </div>

    {{-- Próximas provas --}}
    <div class="card card-pad">
      <h2 class="section-title">Próximas provas</h2>
      @if(count($proximasProvas))
        <ul class="exam-list">
          @foreach($proximasProvas as $p)
            @php
              $colors = ['badge-info', 'badge-danger', 'badge-success', 'badge-warn'];
              $colorIdx = crc32($p['disciplina'] ?? '') % count($colors);
              $badgeCls = $colors[abs($colorIdx)];
            @endphp
            <li>
              <div class="exam-meta">
                <b>{{ $p['disciplina'] }}</b>
                <span>
                  @if($p['professor'] ?? null)Prof. {{ Str::words($p['professor'], 2, '') }} · @endif
                  {{ $p['data_aplicacao'] ? \Carbon\Carbon::parse($p['data_aplicacao'])->format('d/m/Y') : '—' }}
                </span>
              </div>
              <span class="badge {{ $badgeCls }} subject-badge">{{ $p['disciplina'] }}</span>
            </li>
          @endforeach
        </ul>
      @else
        <p style="color:var(--muted);font-size:13px;text-align:center;padding:12px 0;">
          Nenhuma prova agendada.
        </p>
      @endif
    </div>

    {{-- Card motivacional --}}
    <div class="motivation-card">
      <div class="eyebrow" style="color:var(--accent-dark);margin-bottom:8px;">Mensagem de incentivo</div>
      <strong>Mantenha sua rotina de estudos.</strong>
      <p style="margin-top:8px;">Cada prova é uma oportunidade de aprender mais. O Ministério da Educação incentiva você a seguir com foco, organização e confiança no seu desenvolvimento.</p>
    </div>

  </div>
</div>

<div style="height:48px"></div>
@endsection
