@props(['tipo','titulo'])
<button type="button" class="btn painel-grupo-mover" data-painel-grupo-mover="{{ $tipo }}" hidden aria-label="Mover {{ $tipo==='card'?'card':'seção' }} {{ $titulo }}. Arraste ou use as setas para cima e para baixo." title="Mover {{ $tipo==='card'?'card':'seção' }}: arraste ou use as setas"><x-icone nome="arrastar" /></button>
