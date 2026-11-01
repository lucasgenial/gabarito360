@extends('layouts.app')

@section('tela', 'tela-perfis')

{{--
  Arquivo: apps/web/resources/views/membros/perfis.blade.php
  Função: "O que cada perfil pode" — leitura do catálogo (docs/03 v2): permissões padrão,
          extras concedíveis e quem o perfil cadastra. Um perfil por vez (funciona no celular).
--}}

@php
  $descr   = collect($catalogo['permissoes'] ?? [])->keyBy('chave');
  $porPerf = $catalogo['perfis'] ?? [];
  $cadastra = $catalogo['cadastra'] ?? [];
  $ordem   = ['admin_rede', 'dir_nucleo', 'dir_escolar', 'secretario_escolar', 'coordenador', 'professor', 'aplicador', 'aluno', 'secretario_educacao'];
  $modulos = ['escolas' => 'Rede, núcleos e escolas', 'equipe' => 'Equipe e usuários', 'turmas' => 'Turmas e alunos',
              'provas' => 'Provas e gabaritos', 'correcao' => 'Cartões e correção', 'relatorios' => 'Resultados e relatórios'];
@endphp

@section('title', 'O que cada perfil pode')

@section('breadcrumb')
<a href="{{ route('painel') }}">Início</a><span class="sep">/</span>
<a href="{{ route('membros.index') }}">Equipe</a><span class="sep">/</span><span>Perfis</span>
@endsection

@section('content')
<h1 class="page-title" style="margin-top:12px;">O que cada perfil pode</h1>
<p class="page-sub">Permissões que vêm com cada perfil (padrão) e as que podem ser concedidas a uma pessoa (extras). Fonte: catálogo oficial (docs/03).</p>

<div class="chips" role="tablist">
  @foreach($ordem as $p)@if(isset($porPerf[$p]))
    <button type="button" data-perfil="{{ $p }}" class="{{ $loop->first ? 'ativo' : '' }}">{{ $perfis[$p]['label'] ?? $p }}</button>
  @endif @endforeach
</div>

@foreach($ordem as $p)@if(isset($porPerf[$p]))
  @php $g = $porPerf[$p]; @endphp
  <div class="perfil-bloco {{ $loop->first ? 'ativo' : '' }}" id="perfil-{{ $p }}">
    <div class="card card-pad" style="margin-top:12px;font-size:14px;">
      <b>Cadastra:</b>
      {{ !empty($cadastra[$p]) ? collect($cadastra[$p])->map(fn ($x) => $perfis[$x]['label'] ?? $x)->implode(', ') : 'ninguém' }}
    </div>
    <div class="duas-col" style="margin-top:12px;">
      <div class="card card-pad">
        <h3 style="font-size:16px;color:var(--success);">Padrão do perfil ({{ count($g['padrao']) }})</h3>
        @foreach($modulos as $mod => $rot)
          @php $ch = collect($g['padrao'])->filter(fn ($c) => ($descr[$c]['modulo'] ?? '') === $mod); @endphp
          @if($ch->isNotEmpty())
            <div class="eyebrow" style="margin-top:10px;">{{ $rot }}</div>
            <ul class="lista-perm" style="list-style:none;padding:0;margin:4px 0 0;">
              @foreach($ch as $c)<li><span>{{ $descr[$c]['descricao'] ?? $c }}</span><code style="font-size:11px;color:var(--muted);">{{ $c }}</code></li>@endforeach
            </ul>
          @endif
        @endforeach
      </div>
      <div class="card card-pad">
        <h3 style="font-size:16px;color:var(--accent);">Pode receber como extra ({{ count($g['extra']) }})</h3>
        @forelse($g['extra'] as $c)
          <ul class="lista-perm" style="list-style:none;padding:0;margin:4px 0 0;"><li><span>{{ $descr[$c]['descricao'] ?? $c }}</span><code style="font-size:11px;color:var(--muted);">{{ $c }}</code></li></ul>
        @empty
          <p class="field-help">Nenhuma extra para este perfil.</p>
        @endforelse
      </div>
    </div>
  </div>
@endif @endforeach
<div style="height:48px"></div>
@endsection

@push('scripts')
<script>
document.querySelectorAll('.chips button').forEach(function (b) {
  b.addEventListener('click', function () {
    document.querySelectorAll('.chips button').forEach(function (x) { x.classList.toggle('ativo', x === b); });
    document.querySelectorAll('.perfil-bloco').forEach(function (x) { x.classList.toggle('ativo', x.id === 'perfil-' + b.dataset.perfil); });
  });
});
</script>
@endpush
