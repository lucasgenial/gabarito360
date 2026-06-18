@extends('layouts.app')

@section('title', $prova['titulo'])

@push('styles')
<style>
  .status-hero { display:flex;align-items:center;gap:12px;padding:16px 20px;border-radius:var(--radius-md);border:1px solid;margin-top:20px; }
  .info-row { display:flex;gap:10px;align-items:flex-start;padding:10px 0;border-bottom:1px solid var(--border-soft);font-size:14px; }
  .info-row:last-child { border:0; }
  .info-row .lbl { color:var(--muted);min-width:140px;flex-shrink:0;font-weight:500; }
  .detail-grid { display:grid;grid-template-columns:1.5fr 1fr;gap:20px;margin-top:20px; }
  /* Modal */
  .modal-backdrop { position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:1000;display:none;align-items:center;justify-content:center;padding:24px; }
  .modal-backdrop.open { display:flex; }
  .modal { background:var(--surface);border-radius:var(--radius-lg);box-shadow:0 24px 64px rgba(0,0,0,.22);width:100%;max-width:560px;max-height:90vh;overflow-y:auto; }
  .modal-head { padding:20px 24px 16px;border-bottom:1px solid var(--border-soft);display:flex;align-items:center;justify-content:space-between; }
  .modal-head h2 { font-size:17px;font-weight:700; }
  .modal-close { width:32px;height:32px;border-radius:50%;background:none;border:0;cursor:pointer;font-size:20px;color:var(--muted);display:grid;place-items:center; }
  .modal-close:hover { background:var(--surface-2); }
  .modal-body { padding:22px; }
  .modal-foot { padding:14px 22px;border-top:1px solid var(--border-soft);display:flex;justify-content:flex-end;gap:10px; }
  @media(max-width:900px){ .detail-grid{grid-template-columns:1fr;} }
</style>
@endpush

@section('nav')
<a href="{{ route('painel') }}">Painel</a>
<a href="{{ route('provas.index') }}" class="active">Provas</a>
<a href="{{ route('turmas.index') }}">Turmas</a>
<a href="{{ route('escolas.index') }}">Escolas</a>
@endsection

@section('breadcrumb')
<a href="{{ route('painel') }}">Início</a><span class="sep">/</span>
<a href="{{ route('provas.index') }}">Provas</a><span class="sep">/</span>
<span>{{ $prova['titulo'] }}</span>
@endsection

@section('content')

<div class="row-between" style="margin-top:12px;">
  <div>
    <h1 class="page-title">{{ $prova['titulo'] }}</h1>
    <p class="page-sub">{{ $prova['disciplina'] }} · {{ $prova['serie'] }} · {{ $prova['num_questoes'] }} questões</p>
  </div>
  <div style="display:flex;gap:8px;flex-wrap:wrap;">
    @if($prova['status'] === 'rascunho')
      <button class="btn btn-secondary" onclick="abrirModal()">Editar</button>
      <a href="{{ route('provas.gabarito.edit', $prova['id']) }}" class="btn btn-primary">Editar gabarito →</a>
      <form method="POST" action="{{ route('provas.destroy', $prova['id']) }}"
            onsubmit="return confirm('Excluir esta prova?')" style="display:inline;">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn btn-ghost" style="color:var(--danger);">Excluir</button>
      </form>
    @elseif($prova['status'] === 'publicada')
      <button class="btn btn-secondary" onclick="abrirModal()">Editar dados</button>
      <a href="{{ route('provas.gabarito.show', $prova['id']) }}" class="btn btn-secondary">Ver gabarito</a>
      <a href="{{ route('correcao.show', $prova['id']) }}" class="btn btn-primary">Acompanhar correção →</a>
    @elseif($prova['status'] === 'em_correcao')
      <a href="{{ route('provas.gabarito.show', $prova['id']) }}" class="btn btn-secondary">Ver gabarito</a>
      <a href="{{ route('correcao.show', $prova['id']) }}" class="btn btn-primary">Acompanhar correção →</a>
    @elseif($prova['status'] === 'corrigida')
      <a href="{{ route('provas.gabarito.show', $prova['id']) }}" class="btn btn-secondary">Ver gabarito</a>
      <a href="{{ route('relatorios.prova', $prova['id']) }}" class="btn btn-primary">Ver relatório →</a>
    @endif
    <a href="{{ route('provas.index') }}" class="btn btn-ghost">← Voltar</a>
  </div>
</div>

@if(session('sucesso'))
  <div style="background:var(--success-light,#e3f5e1);color:var(--success,#168821);border:1px solid var(--success,#168821);border-radius:var(--radius-md);padding:12px 16px;font-size:14px;font-weight:600;margin-top:16px;">
    {{ session('sucesso') }}
  </div>
@endif
@if(session('erro'))
  <div style="background:var(--danger-light,#fde8e4);color:var(--danger,#e52207);border:1px solid var(--danger,#e52207);border-radius:var(--radius-md);padding:12px 16px;font-size:14px;font-weight:600;margin-top:16px;">
    {{ session('erro') }}
  </div>
@endif

{{-- Status banner --}}
@php
  $statusInfo = [
    'rascunho'    => ['bg'=>'rgba(19,81,180,.08)', 'border'=>'rgba(19,81,180,.3)', 'color'=>'var(--accent)', 'icon'=>'✏️', 'msg'=>'Esta prova está em rascunho. Publique-a quando o gabarito estiver pronto.'],
    'publicada'   => ['bg'=>'rgba(22,136,33,.08)', 'border'=>'rgba(22,136,33,.3)', 'color'=>'var(--success,#168821)', 'icon'=>'✅', 'msg'=>'Prova publicada. Aguardando aplicação e lançamento dos cartões-resposta.'],
    'em_correcao' => ['bg'=>'rgba(245,158,11,.08)', 'border'=>'rgba(245,158,11,.3)', 'color'=>'#b45309', 'icon'=>'🔄', 'msg'=>'Correção em andamento. Os cartões-resposta estão sendo processados.'],
    'corrigida'   => ['bg'=>'rgba(22,136,33,.08)', 'border'=>'rgba(22,136,33,.3)', 'color'=>'var(--success,#168821)', 'icon'=>'🏆', 'msg'=>'Prova corrigida. Resultados disponíveis no relatório.'],
  ];
  $si = $statusInfo[$prova['status']] ?? $statusInfo['rascunho'];
@endphp
<div class="status-hero" style="background:{{ $si['bg'] }};border-color:{{ $si['border'] }};color:{{ $si['color'] }};">
  <span style="font-size:24px;">{{ $si['icon'] }}</span>
  <div>
    <div style="font-weight:700;">Status: {{ ['rascunho'=>'Rascunho','publicada'=>'Publicada','em_correcao'=>'Em correção','corrigida'=>'Corrigida'][$prova['status']] }}</div>
    <div style="font-size:13px;opacity:.8;margin-top:2px;">{{ $si['msg'] }}</div>
  </div>
</div>

{{-- Grid de detalhes --}}
<div class="detail-grid">
  {{-- Dados da prova --}}
  <div class="card card-pad">
    <h3 style="font-size:15px;font-weight:700;margin-bottom:14px;">Dados da prova</h3>
    <div class="info-row">
      <span class="lbl">Título</span>
      <span>{{ $prova['titulo'] }}</span>
    </div>
    <div class="info-row">
      <span class="lbl">Disciplina</span>
      <span>{{ $prova['disciplina'] }}</span>
    </div>
    <div class="info-row">
      <span class="lbl">Série / Ano</span>
      <span>{{ $prova['serie'] }}</span>
    </div>
    <div class="info-row">
      <span class="lbl">Nº de questões</span>
      <span style="font-family:var(--font-mono);">{{ $prova['num_questoes'] }}</span>
    </div>
    <div class="info-row">
      <span class="lbl">Alternativas</span>
      <span>{{ $prova['num_alternativas'] }} por questão</span>
    </div>
    <div class="info-row">
      <span class="lbl">Nota máxima</span>
      <span style="font-family:var(--font-mono);">{{ number_format($prova['nota_maxima'], 1) }}</span>
    </div>
    <div class="info-row">
      <span class="lbl">Tipo de pontuação</span>
      <span>{{ $prova['tipo_pontuacao'] === 'igual' ? 'Igual (todas valem o mesmo)' : 'Personalizado' }}</span>
    </div>
    <div class="info-row">
      <span class="lbl">Data de aplicação</span>
      <span>{{ $prova['data_aplicacao'] ? \Carbon\Carbon::parse($prova['data_aplicacao'])->format('d/m/Y') : '—' }}</span>
    </div>
    <div class="info-row">
      <span class="lbl">Escola</span>
      <span>{{ $prova['escola_nome'] ?? '—' }}</span>
    </div>
    <div class="info-row">
      <span class="lbl">Cartão PDF</span>
      <span>{{ $prova['gerar_cartao_pdf'] ? 'Sim' : 'Não' }}</span>
    </div>
    <div class="info-row">
      <span class="lbl">Anular se todas</span>
      <span>{{ $prova['anular_se_todas'] ? 'Sim' : 'Não' }}</span>
    </div>
  </div>

  {{-- Turmas e próximas etapas --}}
  <div style="display:flex;flex-direction:column;gap:18px;">
    <div class="card card-pad">
      <h3 style="font-size:15px;font-weight:700;margin-bottom:14px;">Turmas vinculadas</h3>
      @if(count($prova['turmas'] ?? []) > 0)
        @foreach($prova['turmas'] as $t)
          <div style="display:flex;align-items:center;gap:10px;padding:8px 0;border-bottom:1px solid var(--border-soft);">
            <div style="width:32px;height:32px;border-radius:6px;background:var(--accent-light);color:var(--accent-dark,#0c326f);display:grid;place-items:center;font-weight:700;font-size:12px;">
              {{ strtoupper(str_replace(['º ','ª '], '', $t['nome'])) }}
            </div>
            <div style="flex:1;">
              <div style="font-weight:600;font-size:13px;">{{ $t['nome'] }}</div>
              <div style="font-size:12px;color:var(--muted);">{{ $t['serie'] }}</div>
            </div>
            @if($prova['status'] === 'corrigida')
              <a href="{{ route('relatorios.turmaProva', [$t['id'], $prova['id']]) }}" class="btn btn-sm btn-ghost">Relatório</a>
            @endif
          </div>
        @endforeach
      @else
        <div style="text-align:center;padding:20px;color:var(--muted);font-size:13px;">
          Nenhuma turma vinculada.<br>
          <button class="btn btn-sm btn-ghost" style="margin-top:8px;" onclick="abrirModal()">Adicionar turmas</button>
        </div>
      @endif
    </div>

    {{-- Próximas etapas --}}
    @if(in_array($prova['status'], ['rascunho', 'publicada']))
    <div class="card card-pad" style="background:var(--accent-light);border-color:rgba(19,81,180,.2);">
      <h3 style="font-size:14px;font-weight:700;margin-bottom:12px;color:var(--accent);">Próximas etapas</h3>
      <div style="display:flex;flex-direction:column;gap:8px;font-size:13px;color:var(--fg-2);">
        @if($prova['status'] === 'rascunho')
          <div style="display:flex;align-items:center;gap:8px;"><span>1️⃣</span> Verifique os dados da prova</div>
          <div style="display:flex;align-items:center;gap:8px;"><span>2️⃣</span> <a href="{{ route('provas.gabarito.edit', $prova['id']) }}" style="color:var(--accent);">Configure o gabarito</a></div>
          <div style="display:flex;align-items:center;gap:8px;"><span>3️⃣</span> Publique o gabarito</div>
          <div style="display:flex;align-items:center;gap:8px;"><span>4️⃣</span> Aplique a prova nas turmas</div>
          <div style="display:flex;align-items:center;gap:8px;"><span>5️⃣</span> Lance os cartões-resposta (OMR)</div>
        @else
          <div style="display:flex;align-items:center;gap:8px;"><span>✅</span> Prova publicada</div>
          <div style="display:flex;align-items:center;gap:8px;"><span>📅</span> Aplique nas turmas em {{ $prova['data_aplicacao'] ? \Carbon\Carbon::parse($prova['data_aplicacao'])->format('d/m/Y') : 'data a definir' }}</div>
          <div style="display:flex;align-items:center;gap:8px;"><span>📷</span> <a href="{{ route('correcao.show', $prova['id']) }}" style="color:var(--accent);">Envie os cartões-resposta para leitura OMR</a></div>
        @endif
      </div>
    </div>
    @endif
  </div>
</div>

{{-- Modal de edição rápida --}}
<div class="modal-backdrop" id="modal-editar" aria-hidden="true">
  <div class="modal" role="dialog" aria-modal="true">
    <div class="modal-head">
      <h2>Editar prova</h2>
      <button type="button" class="modal-close" onclick="fecharModal()">&times;</button>
    </div>
    <form method="POST" action="{{ route('provas.update', $prova['id']) }}">
      @csrf
      @method('PUT')
      <div class="modal-body">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
          <div class="field" style="grid-column:1/-1">
            <label>Título *</label>
            <input class="input" type="text" name="titulo" required value="{{ $prova['titulo'] }}" />
          </div>
          <div class="field">
            <label>Disciplina *</label>
            <input class="input" type="text" name="disciplina" required value="{{ $prova['disciplina'] }}" />
          </div>
          <div class="field">
            <label>Série *</label>
            <input class="input" type="text" name="serie" required value="{{ $prova['serie'] }}" />
          </div>
          <div class="field">
            <label>Data de aplicação</label>
            <input class="input" type="date" name="data_aplicacao" value="{{ $prova['data_aplicacao'] ?? '' }}" />
          </div>
          <div class="field">
            <label>Nota máxima</label>
            <input class="input num" type="number" name="nota_maxima" step="0.5" value="{{ $prova['nota_maxima'] }}" />
          </div>
          <div class="field" style="grid-column:1/-1">
            <label>Turmas</label>
            <div style="display:flex;flex-direction:column;gap:8px;max-height:200px;overflow-y:auto;border:1px solid var(--border-soft);border-radius:var(--radius-sm);padding:10px;">
              @foreach($turmas as $t)
                @php $vinculada = collect($prova['turmas'] ?? [])->contains('id', $t['id']); @endphp
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:13px;">
                  <input type="checkbox" name="turma_ids[]" value="{{ $t['id'] }}" {{ $vinculada ? 'checked' : '' }} />
                  {{ $t['nome'] }} — {{ $t['serie'] }}
                </label>
              @endforeach
            </div>
          </div>
        </div>
      </div>
      <div class="modal-foot">
        <button type="button" class="btn btn-ghost" onclick="fecharModal()">Cancelar</button>
        <button type="submit" class="btn btn-primary">Salvar</button>
      </div>
    </form>
  </div>
</div>

<div style="height:48px;"></div>
@endsection

@push('scripts')
<script>
var modal = document.getElementById('modal-editar');
function abrirModal() { modal.classList.add('open'); modal.setAttribute('aria-hidden','false'); document.body.style.overflow='hidden'; }
function fecharModal() { modal.classList.remove('open'); modal.setAttribute('aria-hidden','true'); document.body.style.overflow=''; }
modal.addEventListener('click', function(e){ if(e.target===modal) fecharModal(); });
document.addEventListener('keydown', function(e){ if(e.key==='Escape') fecharModal(); });
</script>
@endpush
