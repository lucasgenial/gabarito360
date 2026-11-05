{{-- Identidade documental compartilhada. O vínculo ativo define uso autônomo. --}}
@php
  $cab = $cabecalho ?? [];
  $autonomo = \App\Support\Acesso::pessoal();
  $comRede = !empty($cab['rede_id']);
  $logoRede = !$autonomo && !empty($cab['tem_logo_rede']) && $comRede ? route('redes.logo', $cab['rede_id'], false) : null;
  $logoEscola = !$autonomo && !empty($cab['tem_logo']) && !empty($cab['escola_id']) ? route('escolas.logo', $cab['escola_id'], false) : null;
  $esquerda = $comRede ? $logoRede : $logoEscola;
  $direita = $comRede ? $logoEscola : null;
@endphp
<div class="impressao-cab {{ $autonomo ? 'ic-autonomo' : '' }}">
  @if($autonomo)
    <div class="ic-marca"><x-marca variant="horizontal" size="sm" theme="light" /><span>Uso autônomo</span></div>
  @endif
  <div class="ic-topo">
    @if($esquerda)<img class="ic-logo" src="{{ $esquerda }}" alt="{{ $comRede ? 'Logo da rede' : 'Logo da escola' }}">
    @else<div class="ic-logo ic-espaco" aria-hidden="true"></div>@endif
    <div class="ic-textos">
      @if(!$autonomo && !empty($cab['rede']))<div class="ic-rede">{{ $cab['rede'] }}</div>@endif
      <div class="ic-escola">{{ $autonomo ? 'Espaço pessoal: ' : '' }}{{ $cab['nome'] ?? '' }}</div>
      @if(!empty($cab['detalhe']))<div class="ic-detalhe">{{ $cab['detalhe'] }}</div>@endif
    </div>
    @if($direita)<img class="ic-logo" src="{{ $direita }}" alt="Logo da escola">
    @else<div class="ic-logo ic-espaco" aria-hidden="true"></div>@endif
  </div>
  <div class="ic-faixa"><span>{{ $titulo }}</span>@if(!empty($subtitulo))<span class="ic-subtitulo">{{ $subtitulo }}</span>@endif</div>
  <div class="ic-emissao">Emitido em {{ now()->timezone('America/Sao_Paulo')->format('d/m/Y \\à\\s H:i') }}</div>
</div>
