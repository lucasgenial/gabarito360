@props(['id'])
@once
<script src="@ativo('js/libs/cropper-2.3.1.min.js')"></script>
<script src="@ativo('js/preparo-foto-geometria.js')"></script>
<script src="@ativo('js/preparo-foto.js')"></script>
@endonce
<div id="{{ $id }}-editor" class="foto-editor" data-codec-version="{{ \App\Support\Ativos::versao('js/preparo-foto-codecs.js') }}" hidden>
 <p data-foto-status role="status" aria-live="polite"></p>
 <button data-foto-abortar type="button" class="btn btn-secondary" hidden>Cancelar preparação</button>
 <div data-foto-ajustes hidden>
  <p class="field-help">Arraste para enquadrar. Na imagem, use a roda do mouse ou a pinça para ampliar.</p>
  <div class="foto-editor-area"><div class="foto-cropper" data-foto-cropper tabindex="0" role="img" aria-label="Enquadramento da foto. Arraste ou use as setas para mover. Use mais e menos para ampliar ou reduzir."></div></div>
  <div class="foto-rotacao-cabecalho"><label for="{{ $id }}-rotacao">Rotação: <output data-foto-angulo aria-hidden="true">0°</output></label></div>
  <input id="{{ $id }}-rotacao" data-foto-rotacao type="range" min="-180" max="180" step="0.5" value="0" aria-label="Rotação" aria-valuetext="0 graus">
  <div class="foto-rotacao-regua" aria-hidden="true"><span>−180°</span><span>−90°</span><span>0°</span><span>+90°</span><span>+180°</span></div>
  <button data-foto-trocar type="button" class="foto-editor-trocar" aria-controls="{{ $id }}-origem" aria-expanded="false">Trocar foto</button>
 </div>
</div>
