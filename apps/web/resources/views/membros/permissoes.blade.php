@extends('layouts.app')

@section('tela', 'tela-permissoes')

{{--
  Arquivo: apps/web/resources/views/membros/permissoes.blade.php
  Função: Permissões de um membro (docs/03 v2): padrão do perfil (pode ser retirada),
          extras concedíveis (com validade), quem definiu e o histórico. Só aparecem
          como editáveis as permissões que o usuário logado pode definir.
--}}

@php
  $u        = $dados['usuario'];
  $itens    = collect($dados['itens']);
  $editavel = $itens->contains('pode_alterar', true);
  $modulos  = ['escolas' => 'Rede, núcleos e escolas', 'equipe' => 'Equipe e usuários', 'turmas' => 'Turmas e alunos',
               'provas' => 'Provas e gabaritos', 'correcao' => 'Cartões e correção', 'relatorios' => 'Resultados e relatórios'];
  $data     = fn ($d) => $d ? \Carbon\Carbon::parse($d)->timezone('America/Bahia')->format('d/m/Y') : null;
  $acoes    = ['conceder' => 'concedeu', 'retirar' => 'retirou', 'remover' => 'voltou ao padrão'];
@endphp

@section('title', 'Permissões — ' . $u['nome'])

@section('breadcrumb')
<a href="{{ route('painel') }}">Início</a><span class="sep">/</span>
<a href="{{ route('membros.index') }}">Equipe</a><span class="sep">/</span>
<span>Permissões</span>
@endsection

@section('content')
<x-pagina titulo="{{ $u['nome'] }}">
  <x-slot:sub>@if(isset($u['vinculo_id']))Vínculo #{{ $u['vinculo_id'] }} · @endif{{ $perfis[$u['perfil']]['label'] ?? $u['perfil'] }} · {{ count($dados['efetivas']) }} permissões efetivas</x-slot:sub>
    <a class="btn btn-ghost" href="{{ route('membros.perfis') }}">O que cada perfil pode</a>
    <a class="btn btn-secondary" href="{{ route('membros.edit', array_filter(['id' => $u['id'], 'vinculo_id' => $u['vinculo_id'] ?? null])) }}">Dados do membro</a>
</x-pagina>

@if(session('sucesso'))<div class="card card-pad" style="margin-top:12px;border-color:var(--success);color:var(--success);font-weight:600;">{{ session('sucesso') }}</div>@endif
@if(session('erro'))<div class="card card-pad" style="margin-top:12px;border-color:var(--danger);color:var(--danger);font-weight:600;">{{ session('erro') }}</div>@endif

<div class="card card-pad" style="margin-top:12px;font-size:13px;color:var(--muted);">
  <b style="color:var(--fg);">Como funciona:</b> as permissões <b>padrão</b> vêm com o perfil — marque <b>Retirar</b> para tirar uma delas desta pessoa.
  As <b>extras</b> não vêm com o perfil — marque <b>Conceder</b> (opcionalmente até uma data). Você só altera o que também pode fazer.
  @unless($editavel)<br><b>Você pode apenas ver as permissões deste membro.</b>@endunless
</div>

<form method="POST" action="{{ route('membros.permissoes.salvar', array_filter(['id' => $u['id'], 'vinculo_id' => $u['vinculo_id'] ?? null])) }}">
  @csrf @method('PUT')
  @foreach($modulos as $mod => $rotulo)
    @php $doModulo = $itens->where('modulo', $mod); @endphp
    @if($doModulo->isNotEmpty())
      <div class="card card-pad perm-grupo">
        <h3 style="font-size:16px;margin-bottom:4px;">{{ $rotulo }}</h3>
        @foreach($doModulo as $p)
          @php
            $retirada = $p['estado'] === 'retirar';
            $concedida = $p['estado'] === 'conceder';
            $ativa = in_array($p['chave'], $dados['efetivas'], true);
          @endphp
          <div class="perm-item">
            <div class="perm-txt">
              <b class="{{ $retirada ? 'riscado' : '' }}">{{ $p['descricao'] }}</b>
              <span class="badge {{ $p['tipo'] === 'padrao' ? 'badge-info' : 'badge-muted' }}" style="margin-left:6px;">{{ $p['tipo'] === 'padrao' ? 'padrão' : 'extra' }}</span>
              @if($ativa)<span class="badge badge-success" style="margin-left:4px;">ativa</span>@endif
              <div><code>{{ $p['chave'] }}</code></div>
              @if($p['definida_por'])
                <div class="perm-quem">{{ $retirada ? 'Retirada' : 'Concedida' }} por {{ $p['definida_por'] }} em {{ $data($p['definida_em']) }}{{ $p['valida_ate'] ? ' · válida até ' . $data($p['valida_ate']) : '' }}</div>
              @endif
            </div>
            <div class="perm-ctrl">
              @if($p['tipo'] === 'padrao')
                <label><input type="checkbox" name="retirar[{{ $p['chave'] }}]" value="1" {{ $retirada ? 'checked' : '' }} {{ $p['pode_alterar'] ? '' : 'disabled' }}> Retirar</label>
              @else
                <label><input type="checkbox" name="conceder[{{ $p['chave'] }}]" value="1" data-validade="v-{{ $loop->parent->index }}-{{ $loop->index }}" {{ $concedida ? 'checked' : '' }} {{ $p['pode_alterar'] ? '' : 'disabled' }}> Conceder</label>
                <input class="input" type="date" id="v-{{ $loop->parent->index }}-{{ $loop->index }}" name="valida_ate[{{ $p['chave'] }}]" value="{{ $p['valida_ate'] }}" min="{{ now()->toDateString() }}" title="Válida até (opcional)" {{ $p['pode_alterar'] && $concedida ? '' : 'disabled' }}>
              @endif
            </div>
          </div>
        @endforeach
      </div>
    @endif
  @endforeach

  @if($editavel)
    <div class="barra-salvar">
      <a class="btn btn-ghost" href="{{ route('membros.index') }}">Voltar</a>
      <button type="submit" class="btn btn-primary">Salvar permissões</button>
    </div>
  @endif
</form>

<div class="card card-pad" style="margin-top:16px;">
  <h3 style="font-size:16px;">Histórico</h3>
  @forelse($dados['historico'] as $h)
    <div style="font-size:13px;padding:6px 0;border-bottom:1px solid var(--border-soft);">
      {{ $data($h['created_at']) }} — <b>{{ $h['feita_por'] ?? 'sistema' }}</b> {{ $acoes[$h['acao']] ?? $h['acao'] }} <code>{{ $h['permissao'] ?? $h['motivo_tecnico'] ?? '' }}</code>{{ $h['valida_ate'] ? ' (até ' . $data($h['valida_ate']) . ')' : '' }}
    </div>
  @empty
    <p class="field-help">Nenhuma alteração: este membro tem só as permissões padrão do perfil.</p>
  @endforelse
</div>
<div style="height:48px"></div>
@endsection

@push('scripts')
<script>
// Validade só faz sentido para extra concedida
document.querySelectorAll('input[data-validade]').forEach(function (c) {
  c.addEventListener('change', function () { var d = document.getElementById(c.dataset.validade); if (d) { d.disabled = !c.checked; if (!c.checked) d.value = ''; } });
});
</script>
@endpush
