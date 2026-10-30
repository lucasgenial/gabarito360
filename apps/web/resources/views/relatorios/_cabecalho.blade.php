{{--
  Arquivo: apps/web/resources/views/relatorios/_cabecalho.blade.php
  Função: Cabeçalho e rodapé padrão dos relatórios impressos/PDF (A4): logo da escola,
          rede, escola, INEP e cidade/UF, faixa com o título e data de emissão.
          Só aparece na impressão (na tela, a página tem o próprio título).
  Espera: $cabecalho = {escola_id, nome, rede, nucleo, detalhe, tem_logo}, $titulo, $subtitulo (opcional)
--}}
@php $cab = $cabecalho ?? []; @endphp
<div class="impressao-cab">
  <div class="ic-topo">
    @if(!empty($cab['tem_logo']) && !empty($cab['escola_id']))
      <img class="ic-logo" src="{{ route('escolas.logo', $cab['escola_id'], false) }}" alt="Logo da escola">
    @else
      <div class="ic-logo ic-sem-logo">G360</div>
    @endif
    <div class="ic-textos">
      @if(!empty($cab['rede']))<div class="ic-rede">{{ $cab['rede'] }}</div>@endif
      <div class="ic-escola">{{ $cab['nome'] ?? '' }}</div>
      @if(!empty($cab['detalhe']))<div class="ic-detalhe">{{ $cab['detalhe'] }}</div>@endif
    </div>
    <div class="ic-logo ic-espaco"></div>
  </div>
  <div class="ic-faixa"><span>{{ $titulo }}</span>@if(!empty($subtitulo))<span>{{ $subtitulo }}</span>@endif</div>
  <div class="ic-emissao">Emitido em {{ now()->timezone('America/Bahia')->format('d/m/Y \à\s H:i') }}</div>
</div>
<div class="impressao-rodape">Gabarito360 · gabarito360.com.br</div>
