<section class="card card-pad res-historico no-print" id="historico-tentativas" aria-labelledby="historico-titulo" aria-busy="true">
  <h2 id="historico-titulo" tabindex="-1">Histórico de leituras do cartão-resposta</h2>
  <x-carregando rotulo="Carregando leituras…" data-carga-esqueleto role="status"/>
  <div data-carga-conteudo hidden></div><p data-carga-feedback role="status"></p>
  <noscript>Ative o JavaScript para carregar o histórico de leituras do cartão-resposta.</noscript>
</section>
@push('scripts')
@if(empty($dados['cartao_foto_url']))<script src="@ativo('js/leitor-cartao.js')"></script>@endif
<script src="@ativo('js/resultado-historico.js')"></script>
@php($historicoRecurso = \App\Support\NavegacaoWeb::destino('resultados.historico',[$aluno['id'],$prova['id']]+request()->only('tentativa','tentativas_pagina')))
<script type="application/json" id="resultado-historico-config">@json($historicoRecurso)</script>
<script src="@ativo('js/resultado-historico-iniciar.js')"></script>
@endpush
