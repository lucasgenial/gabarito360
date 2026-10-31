@extends('layouts.app')

@section('tela', 'tela-escolas')

@section('title', 'Escolas')

@section('breadcrumb')
<a href="{{ route('painel') }}">Início</a><span class="sep">/</span><span>Escolas</span>
@endsection

@section('content')

<x-pagina titulo="Escolas">
  <x-slot:sub>Gerencie todas as unidades escolares cadastradas no sistema</x-slot:sub>
    <div style="position:relative;max-width:100%;">
      <svg style="position:absolute;left:12px;top:50%;transform:translateY(-50%);opacity:.4;" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
      <input id="search-escolas" type="search" class="input" placeholder="Buscar escola…"
             style="padding-left:36px;width:240px;max-width:100%;" value="{{ $busca }}"
             oninput="filtrarEscolas(this.value)" />
    </div>
    @pode('escola.criar')
    <a class="btn btn-primary" href="{{ route('escolas.create') }}">
      <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>
      Nova escola
    </a>
    @endpode
</x-pagina>

@if(session('sucesso'))<x-aviso tipo="sucesso">{{ session('sucesso') }}</x-aviso>@endif
@if(session('erro'))<x-aviso tipo="erro">{{ session('erro') }}</x-aviso>@endif

{{-- KPIs --}}
{{-- Grid de escolas --}}
<div class="escola-grid" id="escola-grid">
  @forelse($escolas as $e)
    <div class="escola-card {{ !$e['ativo'] ? 'inativa' : '' }}" data-nome="{{ strtolower($e['nome']) }}">
      <div class="escola-card-head">
        <div class="escola-icon"><x-icone nome="escola" /></div>
        <div style="flex:1;min-width:0;">
          <div class="escola-name">{{ $e['nome'] }}</div>
          <div class="escola-code">INEP: {{ $e['inep'] }}</div>
          @if(($e['tipo'] ?? 'sede') !== 'sede')
            <div class="escola-code">{{ $e['tipo'] === 'anexo' ? 'Anexo' : 'Unidade vinculada' }}{{ !empty($e['sede_nome']) ? ' de ' . $e['sede_nome'] : '' }}</div>
          @endif
        </div>
        <div style="margin-left:auto;flex-shrink:0;">
          @if($e['ativo'])
            <span class="badge badge-success">Ativa</span>
          @else
            <span class="badge badge-danger">Inativa</span>
          @endif
        </div>
      </div>
      <div class="escola-card-body">
        <div class="escola-meta">
          @if($e['logradouro'] ?? null)
            <div class="escola-meta-row">
              <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
              {{ $e['logradouro'] }}@if($e['cidade'] ?? null), {{ $e['cidade'] }} / {{ $e['uf'] ?? '' }}@endif
            </div>
          @endif
          @if($e['telefone'] ?? null)
            <div class="escola-meta-row">
              <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12 19.79 19.79 0 0 1 1.63 3.18 2 2 0 0 1 3.6 1h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 8.6a16 16 0 0 0 6 6l.96-.96a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
              {{ $e['telefone'] }}
            </div>
          @endif
          @if($e['email'] ?? null)
            <div class="escola-meta-row">
              <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg>
              {{ $e['email'] }}
            </div>
          @endif
          <div class="escola-meta-row">
            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="4" y="2" width="16" height="20" rx="2"/><path d="M9 9h6M9 13h6M9 17h4"/></svg>
            {{ ['estadual'=>'Estadual','municipal'=>'Municipal','federal'=>'Federal','privada'=>'Privada'][$e['tipo_rede']] ?? $e['tipo_rede'] }}
            @if($e['nucleo_nome'] ?? null) · {{ $e['nucleo_nome'] }}@endif
          </div>
        </div>
      </div>
      <div class="escola-card-foot">
        <div class="escola-stats">
          <div class="escola-stat"><div class="val">{{ $e['total_alunos'] ?? 0 }}</div><div class="lbl">Alunos</div></div>
          <div class="escola-stat"><div class="val">{{ $e['total_turmas'] ?? 0 }}</div><div class="lbl">Turmas</div></div>
          <div class="escola-stat"><div class="val">{{ $e['total_provas'] ?? 0 }}</div><div class="lbl">Provas</div></div>
        </div>
        <div class="escola-actions">
          @pode('escola.editar')
          <a class="btn btn-sm btn-ghost" href="{{ route('escolas.edit', $e['id']) }}">Editar</a>
          @endpode
          <a href="{{ route('escolas.show', $e['id']) }}" class="btn btn-sm btn-primary">Ver mais</a>
        </div>
      </div>
    </div>
  @empty
    <div style="grid-column:1/-1;text-align:center;padding:60px 20px;color:var(--muted);">
      <div class="icone-grande"><x-icone nome="escola" /></div>
      <h3 style="font-size:18px;font-weight:700;color:var(--fg-2);margin-bottom:8px;">Nenhuma escola encontrada</h3>
      @pode('escola.criar')<p>Cadastre a primeira escola usando o botão "Nova escola".</p>@endpode
    </div>
  @endforelse
</div>

{{-- Modal de criação --}}
@endsection

@push('scripts')
<script>
function filtrarEscolas(busca) {
  var termo = busca.toLowerCase().trim();
  var cards = document.querySelectorAll('#escola-grid .escola-card');
  cards.forEach(function(card) {
    card.style.display = (!termo || card.dataset.nome.includes(termo)) ? '' : 'none';
  });
}
</script>
@endpush
