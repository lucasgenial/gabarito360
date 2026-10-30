@props(['rotulo', 'valor', 'detalhe' => null, 'tipo' => null])
{{-- Indicador: <x-indicador rotulo="Alunos" valor="54" detalhe="matrículas ativas" tipo="sucesso|perigo|destaque" /> --}}
<div class="g-indicador {{ $tipo }}">
  <div class="g-indicador-rotulo">{{ $rotulo }}</div>
  <div class="g-indicador-valor">{{ $valor }}</div>
  @if($detalhe)<div class="g-indicador-detalhe">{{ $detalhe }}</div>@endif
</div>
