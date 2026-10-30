{{--
  Arquivo: apps/web/resources/views/relatorios/_cabecalho.blade.php
  Função: Cabeçalho padrão (o rodapé com o número da página vem do @page no app.css) dos relatórios impressos/PDF (A4): logo do ente
          (rede ou mantenedora) à esquerda e da escola à direita,
          rede, escola, INEP e cidade/UF, faixa com o título e data de emissão.
          Só aparece na impressão (na tela, a página tem o próprio título).
  Espera: $cabecalho = {escola_id, nome, rede, nucleo, detalhe, tem_logo}, $titulo, $subtitulo (opcional)
--}}
@php $cab = $cabecalho ?? []; @endphp
<div class="impressao-cab">
  <div class="ic-topo">
    @php
      $logoEnte   = !empty($cab['tem_logo_rede']) && !empty($cab['rede_id']) ? route('redes.logo', $cab['rede_id'], false) : null;
      $logoEscola = !empty($cab['tem_logo']) && !empty($cab['escola_id']) ? route('escolas.logo', $cab['escola_id'], false) : null;
      $esquerda   = $logoEnte ?? $logoEscola;
      $direita    = $logoEnte ? $logoEscola : null;
    @endphp
    @if($esquerda)
      <img class="ic-logo" src="{{ $esquerda }}" alt="Logo">
    @else
      <div class="ic-logo ic-sem-logo">G360</div>
    @endif
    <div class="ic-textos">
      @if(!empty($cab['rede']))<div class="ic-rede">{{ $cab['rede'] }}</div>@endif
      <div class="ic-escola">{{ $cab['nome'] ?? '' }}</div>
      @if(!empty($cab['detalhe']))<div class="ic-detalhe">{{ $cab['detalhe'] }}</div>@endif
    </div>
    @if($direita)
      <img class="ic-logo" src="{{ $direita }}" alt="Logo da escola">
    @else
      <div class="ic-logo ic-espaco"></div>
    @endif
  </div>
  <div class="ic-faixa"><span>{{ $titulo }}</span>@if(!empty($subtitulo))<span>{{ $subtitulo }}</span>@endif</div>
  <div class="ic-emissao">Emitido em {{ now()->timezone('America/Bahia')->format('d/m/Y \à\s H:i') }}</div>
</div>
