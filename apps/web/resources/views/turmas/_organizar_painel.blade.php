<div class="editor-paineis" data-editor-painel>
 <div class="editor-paineis-barra">
  <button type="button" class="btn btn-secondary" data-editor-organizar disabled><x-icone nome="editar" /> Organizar painel</button>
  <button type="button" class="btn btn-secondary" data-editor-consultar hidden><x-icone nome="atualizar" /> Consultar estado salvo</button>
 </div>
 <section data-editor-edicao hidden aria-label="Editar composição do painel">
  <div class="barra-editor">
   <div role="tablist" aria-label="Organização do painel">
    <button type="button" class="btn btn-secondary" id="pe-tab-distribuicao" role="tab" aria-controls="pe-distribuicao" aria-selected="true" data-editor-aba="distribuicao"><x-icone nome="turma" /> Distribuição por seção</button>
    <button type="button" class="btn btn-secondary" id="pe-tab-fino" role="tab" aria-controls="pe-fino" aria-selected="false" tabindex="-1" data-editor-aba="fino"><x-icone nome="editar" /> Ajustes finos</button>
   </div>
   <div class="editor-paineis-acoes">
    <button type="button" class="btn btn-secondary" data-editor-novo><x-icone nome="adicionar" /> Novo painel</button>
    <button type="button" class="btn btn-secondary" data-editor-ver><x-icone nome="ver" /> Visualizar painel</button>
    <button type="button" class="btn btn-ghost" data-editor-restaurar><x-icone nome="atualizar" /> Restaurar padrão</button>
    <button type="button" class="btn btn-secondary" data-editor-cancelar>Cancelar</button>
    <button type="button" class="btn btn-primary" data-editor-salvar><x-icone nome="salvar" /> Salvar disposição</button>
   </div>
  </div>
  <div id="pe-distribuicao" role="tabpanel" aria-labelledby="pe-tab-distribuicao"><div class="quadro" data-editor-quadro></div></div>
  <div id="pe-fino" role="tabpanel" aria-labelledby="pe-tab-fino" hidden><div data-editor-fino></div></div>
 </section>
 <p class="edicao-suspensa" data-editor-suspenso hidden>O rascunho foi preservado. Volte ao desktop para continuar a edição.</p>
 <div class="barra-preview" data-editor-preview hidden><span>Prévia das alterações ainda não salvas.</span><button type="button" class="btn btn-secondary" data-editor-voltar>Voltar à edição</button></div>
 <div data-editor-leitura></div>
 <p role="status" aria-live="polite" data-editor-status></p>
 <dialog class="editor-dialog" data-editor-confirmacao aria-labelledby="pe-confirmar-titulo">
  <h2 id="pe-confirmar-titulo"></h2><p data-editor-confirmar-texto></p>
  <div class="editor-paineis-acoes"><button type="button" class="btn btn-secondary" data-editor-nao>Cancelar</button><button type="button" class="btn btn-primary" data-editor-sim>Confirmar</button></div>
 </dialog>
 <dialog class="editor-dialog" data-editor-novo-dialog aria-labelledby="pe-novo-titulo">
  <form data-editor-novo-form>
   <h2 id="pe-novo-titulo">Novo painel</h2>
   <div class="field"><label for="pe-novo-nome">Nome do painel</label><input class="input" id="pe-novo-nome" name="titulo" maxlength="60" required autocomplete="off"></div>
   <div class="field"><label for="pe-novo-pai">Seção</label><select class="select" name="pai" id="pe-novo-pai"></select></div>
   <p role="alert" data-editor-novo-erro></p>
   <div class="editor-paineis-acoes"><button type="button" class="btn btn-secondary" data-editor-novo-cancelar>Cancelar</button><button type="submit" class="btn btn-primary">Criar painel</button></div>
  </form>
 </dialog>
 <template data-editor-icones>@foreach(['editar','arrastar','fixar','seta','arquivo','confirmar','fechar','atualizar'] as $icone)<span data-icone="{{ $icone }}"><x-icone :nome="$icone" /></span>@endforeach</template>
</div>
@php
$alvosPainel=['painel'=>$paginaPainel];
if($paginaPainel==='turma')$alvosPainel['turmaId']=$turmaPainel??$turma['id'];
@endphp
<script type="application/json" id="painel-editor-config">{!! json_encode(['painel'=>$paginaPainel,'bootstrap'=>$editorBootstrap??null,'consultar'=>\App\Support\NavegacaoWeb::destino('paineis.preferencias.consultar',$alvosPainel),'salvar'=>\App\Support\NavegacaoWeb::destino('paineis.preferencias.salvar',$alvosPainel)],JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT) !!}</script>
@once
@push('styles')<link rel="stylesheet" href="{{ asset('css/paineis-personalizaveis.css') }}?v={{ @filemtime(public_path('css/paineis-personalizaveis.css')) }}"><link rel="stylesheet" href="{{ asset('css/editor-paineis.css') }}?v={{ @filemtime(public_path('css/editor-paineis.css')) }}">@endpush
@push('scripts')<script src="{{ asset('js/painel-graficos-opcoes.js') }}?v={{ @filemtime(public_path('js/painel-graficos-opcoes.js')) }}" defer></script><script src="{{ asset('js/editor-paineis.js') }}?v={{ @filemtime(public_path('js/editor-paineis.js')) }}" defer></script>@endpush
@endonce
