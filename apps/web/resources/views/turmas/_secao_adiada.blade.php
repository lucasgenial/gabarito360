{{-- F4-B: aba carregada na primeira abertura. A URL vira referência UR4 na resposta e é conferida pelo BFF. --}}
<div class="tu1-secao-adiada" data-turma-secao-adiada data-turma-secao-url="{{ route('turmas.secao', ['id'=>$turma['id'],'secao'=>$secaoAdiada]) }}" aria-busy="false">
  <x-carregando :rotulo="$rotuloAdiado" data-carga-esqueleto role="status" hidden/>
  <div data-carga-conteudo hidden></div><p data-carga-feedback role="status"></p>
  <noscript><p>Ative o JavaScript ou <a href="{{ route('turmas.show', ['id'=>$turma['id'],'secao'=>$secaoAdiada]) }}">abra esta seção da turma</a>.</p></noscript>
</div>
