{{-- Leitor comum de documento: painel no desktop, modal nas telas compactas. URL já autorizada. --}}
<aside class="card rev-documento-painel no-print" id="doc-painel" aria-labelledby="doc-titulo" hidden>
  <header class="rev-modal-cabecalho"><div><h2 id="doc-titulo">Cartão-resposta completo</h2><p>{{ $documentoContexto }}</p></div></header>
  <div id="doc-desktop" class="rev-documento-destino"><div id="doc-leitor"></div></div>
</aside>
<dialog id="doc-modal" class="rev-cartao-modal no-print" aria-labelledby="doc-modal-titulo" aria-describedby="doc-modal-contexto">
  <header class="rev-modal-cabecalho"><div><h2 id="doc-modal-titulo">Cartão-resposta</h2><p id="doc-modal-contexto">{{ $documentoContexto }}</p></div>
    <button type="button" class="btn btn-secondary" id="doc-fechar" autofocus>Fechar</button></header>
  <div id="doc-mobile" class="rev-documento-destino"></div>
</dialog>
@push('scripts')
<script src="@ativo('js/leitor-cartao.js')"></script>
<script src="@ativo('js/painel-cartao.js')"></script>
@php($g360Config = ['documento'=>is_array($documentoUrl)?$documentoUrl:\App\Support\NavegacaoWeb::deUrl($documentoUrl),'opcoes'=>$documentoOpcoes??[]])
<script type="application/json" id="cartao-documento-config">@json($g360Config)</script>
<script src="@ativo('js/cartao-documento.js')"></script>
@endpush
