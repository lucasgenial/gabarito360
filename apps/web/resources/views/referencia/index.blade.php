@extends('layouts.app')

{{--
  Arquivo: apps/web/resources/views/referencia/index.blade.php
  Função: Referência visual (docs/23, L1): tokens, régua de larguras e componentes base.
          As telas migradas nas etapas L3 a L7 usam só o que está aqui.
--}}

@section('title', 'Referência visual')

@section('content')
<x-pagina chamada="Padrão visual" titulo="Referência visual" subtitulo="Componentes e medidas usados em todas as telas.">
  <button type="button" class="btn btn-secondary">Secundário</button>
  <button type="button" class="btn btn-primary">Principal</button>
</x-pagina>

<x-indicadores>
  <x-indicador rotulo="Normal" valor="54" detalhe="sem cor" />
  <x-indicador rotulo="Destaque" valor="1,8" detalhe="azul" tipo="destaque" />
  <x-indicador rotulo="Sucesso" valor="12" detalhe="verde" tipo="sucesso" />
  <x-indicador rotulo="Perigo" valor="3" detalhe="vermelho" tipo="perigo" />
</x-indicadores>

<x-aviso tipo="sucesso">Aviso de sucesso.</x-aviso>
<x-aviso tipo="erro">Aviso de erro.</x-aviso>
<x-aviso tipo="atencao">Aviso de atenção.</x-aviso>
<x-aviso tipo="info">Aviso informativo.</x-aviso>

<x-cartao titulo="Tipografia">
  <x-slot:acoes><span class="badge badge-info">Selo</span></x-slot:acoes>
  @foreach(['3xl' => '36', '2xl' => '28', 'xl' => '22', 'lg' => '18', 'md' => '16', 'sm' => '14', 'xs' => '12'] as $n => $px)
    <div style="font-size:var(--text-{{ $n }});">text-{{ $n }} · {{ $px }} px · 1234567890</div>
  @endforeach
</x-cartao>

<x-cartao titulo="Cores">
  <div style="display:flex;flex-wrap:wrap;gap:10px;">
    @foreach(['accent', 'accent-dark', 'accent-light', 'success', 'success-light', 'warn', 'warn-light', 'danger', 'danger-light', 'surface-2', 'border'] as $c)
      <div style="width:120px;font-size:var(--text-xs);"><div style="height:48px;border-radius:8px;border:1px solid var(--border);background:var(--{{ $c }});"></div>{{ $c }}</div>
    @endforeach
  </div>
</x-cartao>

<x-cartao titulo="Régua de larguras">
  <p class="field-help">xs &lt; 480 · sm 480–767 · md 768–1023 · lg 1024–1439 · xl 1440–1919 · 2xl ≥ 1920</p>
  <p class="field-help">Escala da área logada: 1,125 a partir de 1920 px · 1,25 a partir de 2560 px · 1,5 a partir de 3200 px.</p>
</x-cartao>

<x-cartao titulo="Ícones">
  <div style="display:flex;flex-wrap:wrap;gap:18px;">
    @foreach(['casa', 'prova', 'camera', 'turma', 'aluno', 'escola', 'equipe', 'grafico', 'mais'] as $i)
      <span style="font-size:var(--text-xs);text-align:center;"><x-icone :nome="$i" /><br>{{ $i }}</span>
    @endforeach
  </div>
</x-cartao>

<x-cartao titulo="Estado vazio">
  <x-vazio titulo="Nenhuma prova cadastrada" icone="prova">Crie a primeira prova para começar.</x-vazio>
</x-cartao>

<x-cartao titulo="Coluna de leitura">
  <div class="coluna-leitura">
    <div class="field"><label for="ex">Campo de exemplo</label><input class="input" id="ex" placeholder="Formulários ficam nesta largura"></div>
  </div>
</x-cartao>
<div style="height:48px;"></div>
@endsection
