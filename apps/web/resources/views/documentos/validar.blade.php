@extends('layouts.public')
@section('title', 'Conferir documento — Gabarito360')
@section('robots', 'noindex, nofollow')
@section('description', 'Confira a emissão e a integridade de um relatório do Gabarito360.')
@section('content')
<section class="card card-pad documento-validacao" aria-labelledby="validacao-titulo">
  <h1 id="validacao-titulo">Conferir documento</h1>
  <p>Consulte o código do relatório. Para conferir também se o PDF foi alterado, selecione o arquivo.</p>
  <details class="orientacao"><summary>Saiba mais +</summary><p>O código confirma o registro de emissão. Somente a comparação do arquivo confirma sua integridade. O PDF permanece no seu dispositivo: enviamos apenas seu resumo digital, sem o conteúdo. Este selo é emitido pelo sistema e não substitui uma assinatura pessoal com certificado. Relatórios antigos sem código não podem ser conferidos aqui.</p></details>
  <form id="documento-validacao" action="{{ route('documentos.conferir') }}" method="post">
    @csrf <x-revisao-acesso />
    <div class="form-group"><label for="codigo-documento">Código do documento</label><input class="form-control" id="codigo-documento" name="codigo" value="{{ $codigo }}" required maxlength="37" pattern="[Gg]360-[a-fA-F0-9]{32}" autocomplete="off" spellcheck="false" aria-describedby="codigo-ajuda"><small id="codigo-ajuda">Começa com G360- e está no rodapé do PDF.</small></div>
    <div class="form-group"><label for="arquivo-documento">PDF para comparar <span>(opcional)</span></label><small id="arquivo-ajuda">Até 50 MB. O arquivo será conferido no seu dispositivo.</small><input class="form-control" id="arquivo-documento" type="file" accept="application/pdf,.pdf" aria-describedby="arquivo-ajuda"></div>
    <button type="submit" class="btn btn-primary">Conferir documento</button>
  </form>
  <div id="documento-status" class="g-aviso info" role="status" aria-live="polite" hidden></div>
  <noscript><x-aviso tipo="atencao">Ative o JavaScript para conferir o documento e manter o arquivo no seu dispositivo.</x-aviso></noscript>
</section>
@endsection
@push('styles')
<link rel="stylesheet" href="@ativo('css/validacao-documento.css')">
@endpush
@push('scripts')
<script src="@ativo('js/validacao-documento.js')" defer></script>
@endpush
