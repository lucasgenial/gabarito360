@extends('layouts.app')

@section('tela', 'tela-prova')

@section('title', $prova['titulo'])

@section('breadcrumb')
<a href="{{ route('painel') }}">Início</a><span class="sep">/</span>
<a href="{{ route('provas.index') }}">Provas</a><span class="sep">/</span>
<span>{{ $prova['titulo'] }}</span>
@endsection

@section('content')

<x-pagina titulo="{{ $prova['titulo'] }}">
  <x-slot:sub>{{ $prova['disciplina'] }} · {{ $prova['serie'] }} · {{ $prova['num_questoes'] }} questões</x-slot:sub>
    @if($prova['status'] === 'rascunho')
      @pode('prova.editar')
      <button class="btn btn-secondary" onclick="abrirModal()">Editar</button>
      <a href="{{ route('provas.gabarito.edit', $prova['id']) }}" class="btn btn-primary">Editar gabarito →</a>
      <form method="POST" action="{{ route('provas.destroy', $prova['id']) }}"
            onsubmit="return confirm('Excluir esta prova?')" style="display:inline;">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn btn-ghost" style="color:var(--danger);">Excluir</button>
      </form>
      @endpode
    @elseif($prova['status'] === 'publicada')
      @pode('prova.editar')<button class="btn btn-secondary" onclick="abrirModal()">Editar dados</button>@endpode
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
</x-pagina>

@if(session('sucesso'))<x-aviso tipo="sucesso">{{ session('sucesso') }}</x-aviso>@endif
@if(session('erro'))<x-aviso tipo="erro">{{ session('erro') }}</x-aviso>@endif

{{-- Status --}}
@php
  $statusInfo = [
    'rascunho'    => ['info', 'Rascunho', 'Publique a prova quando o gabarito estiver pronto.'],
    'publicada'   => ['sucesso', 'Publicada', 'Aguardando a aplicação e o envio dos cartões-resposta.'],
    'em_correcao' => ['atencao', 'Em correção', 'Os cartões-resposta estão sendo processados.'],
    'corrigida'   => ['sucesso', 'Corrigida', 'Resultados disponíveis no relatório.'],
  ];
  [$tipoStatus, $rotuloStatus, $msgStatus] = $statusInfo[$prova['status']] ?? $statusInfo['rascunho'];
@endphp
<x-aviso :tipo="$tipoStatus">Status: {{ $rotuloStatus }}. <span style="font-weight:400;">{{ $msgStatus }}</span></x-aviso>

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
            <div style="width:32px;height:32px;border-radius:6px;background:var(--accent-light);color:var(--accent-dark);display:grid;place-items:center;font-weight:700;font-size:12px;">
              {{ strtoupper(str_replace(['º ','ª '], '', $t['nome'])) }}
            </div>
            <div style="flex:1;">
              <div style="font-weight:600;font-size:13px;">{{ $t['nome'] }}</div>
              <div style="font-size:12px;color:var(--muted);">{{ $t['serie'] }}</div>
            </div>
            @pode('cartao.imprimir')
            <a href="{{ route('correcao.pdf', $prova['id']) }}?turma_id={{ $t['id'] }}" target="_blank" class="btn btn-sm btn-ghost" title="Cartões-resposta com nome e QR de cada aluno"><x-icone nome="imprimir" /> Cartões</a>
            @endpode
            @if(in_array($prova['status'], ['em_correcao', 'corrigida'], true))
              <a href="{{ route('relatorios.turmaProva', [$t['id'], $prova['id']]) }}" class="btn btn-sm btn-ghost">Relatório</a>
            @endif
          </div>
        @endforeach
        @pode('cartao.imprimir')
        <a href="{{ route('correcao.pdf', $prova['id']) }}?brancos=5" target="_blank" class="btn btn-sm btn-ghost" style="margin-top:8px;"><x-icone nome="imprimir" /> 5 cartões em branco</a>
        @endpode
      @else
        <div style="text-align:center;padding:20px;color:var(--muted);font-size:13px;">
          Nenhuma turma vinculada.<br>
          <button class="btn btn-sm btn-ghost" style="margin-top:8px;" onclick="abrirModal()">Adicionar turmas</button>
        </div>
      @endif
    </div>

    {{-- Próximas etapas --}}
    @if(in_array($prova['status'], ['rascunho', 'publicada']))
    <div class="card card-pad" style="background:var(--accent-light);border-color:rgba(29,79,158,.2);">
      <h3 style="font-size:14px;font-weight:700;margin-bottom:12px;color:var(--accent);">Próximas etapas</h3>
      <div style="display:flex;flex-direction:column;gap:8px;font-size:13px;color:var(--fg-2);">
        @if($prova['status'] === 'rascunho')
          <div style="display:flex;align-items:center;gap:8px;"><span class="passo">1</span> Verifique os dados da prova</div>
          <div style="display:flex;align-items:center;gap:8px;"><span class="passo">2</span> <a href="{{ route('provas.gabarito.edit', $prova['id']) }}" style="color:var(--accent);">Configure o gabarito</a></div>
          <div style="display:flex;align-items:center;gap:8px;"><span class="passo">3</span> Publique o gabarito</div>
          <div style="display:flex;align-items:center;gap:8px;"><span class="passo">4</span> Aplique a prova nas turmas</div>
          <div style="display:flex;align-items:center;gap:8px;"><span class="passo">5</span> Lance os cartões-resposta</div>
        @else
          <div style="display:flex;align-items:center;gap:8px;"><span class="passo">1</span> Prova publicada</div>
          <div style="display:flex;align-items:center;gap:8px;"><span class="passo">2</span> Aplique nas turmas em {{ $prova['data_aplicacao'] ? \Carbon\Carbon::parse($prova['data_aplicacao'])->format('d/m/Y') : 'data a definir' }}</div>
          <div style="display:flex;align-items:center;gap:8px;"><span class="passo">3</span> <a href="{{ route('correcao.show', $prova['id']) }}" style="color:var(--accent);">Envie os cartões-resposta para leitura</a></div>
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
