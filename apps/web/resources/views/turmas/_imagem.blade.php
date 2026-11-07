@php
$imagemInicial=['avatar_id'=>$turma['avatar_id']??null,'tem_foto'=>(bool)($turma['tem_foto']??false),'imagem_versao'=>$turma['imagem_versao']??1,'foto_url'=>\App\Support\NavegacaoWeb::destino('turmas.foto',['id'=>$turma['id'],'v'=>$turma['imagem_versao']??1])];
@endphp
<dialog id="imagem-turma-modal" class="troca-contexto-modal imagem-modal" aria-labelledby="imagem-turma-titulo" aria-describedby="imagem-turma-orientacao">
 <div class="troca-contexto-cabecalho"><h2 id="imagem-turma-titulo">Imagem da turma</h2><button class="btn btn-secondary" id="imagem-turma-fechar" type="button" aria-label="Fechar imagem da turma">Fechar</button></div>
 <p id="imagem-turma-orientacao">Escolha um brasão ou uma foto para identificar esta turma. Salve para confirmar.@if($imagemTurmaEdicao??false) A imagem é salva separadamente dos demais dados da turma.@endif</p>
 <p id="imagem-turma-erro" class="field-error" role="alert" tabindex="-1" hidden></p>
 <p id="imagem-turma-sucesso" role="status" hidden></p>
 <div id="imagem-turma-preview" class="imagem-modal-preview" aria-label="Prévia da imagem"></div>
 <form id="imagem-turma-form" method="POST" action="{{ route('turmas.imagem',$turma['id']) }}" enctype="multipart/form-data">
 @csrf <x-revisao-acesso />
 <fieldset class="imagem-modal-tipos"><legend class="sr-only">Escolha a imagem</legend><label><input type="radio" name="tipo" value="avatar"> Brasão</label><label><input type="radio" name="tipo" value="foto"> Foto</label></fieldset>
 <div id="imagem-turma-avatares"><x-seletor-avatar catalogo="turmas" :direto="true" titulo="Escolha um brasão" :nome="$turma['nome']" :avatar-id="$turma['avatar_id']??null" preview-id="imagem-turma-avatar-preview" /></div>
 <div id="imagem-turma-foto" class="field">
 <div id="imagem-turma-origem" class="imagem-modal-acoes foto-origem"><button type="button" class="btn btn-secondary" data-camera>Tirar foto</button><button type="button" class="btn btn-secondary" data-arquivo>Escolher arquivo</button></div>
 <input hidden type="file" id="imagem-turma-arquivo" name="foto" accept="image/jpeg,image/png,image/webp,image/heic,image/heif,.heic,.heif,.dng">
 <input hidden type="file" id="imagem-turma-camera" accept="image/*" capture="user">
 <p class="field-help">A foto será ajustada e compactada antes de enviar. A câmera depende do celular e do navegador.</p>
 <x-preparo-foto id="imagem-turma" />
 <details class="field-help"><summary>Formatos e limites</summary><p>Fotos de até 32 MB e 48 megapixels. JPG, PNG e WebP; HEIC/HEIF são convertidos. RAW/DNG exige uma prévia JPG incorporada compatível. Se não houver, escolha a versão JPG/HEIC ou use o modo padrão da câmera. O original não é enviado nem armazenado.</p></details>
 </div>
 <div class="imagem-modal-acoes imagem-modal-rodape"><button class="btn btn-secondary" id="imagem-turma-cancelar" type="button">Cancelar</button><button class="btn btn-primary" id="imagem-turma-salvar" type="submit" disabled>Salvar imagem</button></div>
 </form>
</dialog>
@php($g360TurmaConfig=['inicial'=>$imagemInicial,'acao'=>\App\Support\NavegacaoWeb::destino('turmas.imagem',['id'=>$turma['id']]),'turma'=>$turma['id']])
@push('scripts')
<script type="application/json" id="imagem-turma-config">@json($g360TurmaConfig)</script>
<script src="{{ asset('js/imagem-turma.js') }}?v={{ filemtime(public_path('js/imagem-turma.js')) }}" defer></script>
@endpush
