@php
 $imagemInicial=['avatar_id'=>session('auth_avatar_id'),'tem_foto'=>(bool)session('auth_tem_foto',false),'imagem_versao'=>session('auth_imagem_versao',1),'foto_url'=>\App\Support\NavegacaoWeb::destino('perfil.foto')];
@endphp
<dialog id="imagem-usuario-modal" class="troca-contexto-modal imagem-modal" aria-labelledby="imagem-usuario-titulo" aria-describedby="imagem-usuario-orientacao">
 <div class="troca-contexto-cabecalho"><h2 id="imagem-usuario-titulo">Sua imagem de perfil</h2><button class="btn btn-secondary" id="imagem-usuario-fechar" type="button" aria-label="Fechar imagem de perfil">Fechar</button></div>
 <p id="imagem-usuario-orientacao">A imagem é da sua conta e aparece nos seus vínculos. Escolha uma opção e salve para confirmar.</p>
 <p id="imagem-usuario-erro" class="field-error" role="alert" tabindex="-1" hidden></p>
 <p id="imagem-usuario-sucesso" role="status" hidden></p>
 <div id="imagem-usuario-preview" class="imagem-modal-preview" aria-label="Prévia da imagem"></div>
 <form id="imagem-usuario-form" method="POST" action="{{ route('perfil.imagem') }}" enctype="multipart/form-data">
 @csrf <x-revisao-acesso />
 <fieldset class="imagem-modal-tipos"><legend class="sr-only">Escolha a imagem</legend><label><input type="radio" name="tipo" value="avatar"> Avatar</label><label><input type="radio" name="tipo" value="foto"> Foto</label></fieldset>
 <div id="imagem-usuario-avatares"><x-seletor-avatar :direto="true" titulo="Escolha um avatar" :nome="session('auth_nome','Usuário')" :avatar-id="session('auth_avatar_id')" preview-id="imagem-avatar-preview" /></div>
 <div id="imagem-usuario-foto" class="field">
 <div id="imagem-usuario-origem" class="imagem-modal-acoes foto-origem"><button type="button" class="btn btn-secondary" data-camera>Tirar foto</button><button type="button" class="btn btn-secondary" data-arquivo>Escolher arquivo</button></div>
 <input hidden type="file" id="imagem-usuario-arquivo" name="foto" accept="image/jpeg,image/png,image/webp,image/heic,image/heif,.heic,.heif,.dng">
 <input hidden type="file" id="imagem-usuario-camera" accept="image/*" capture="user">
 <p class="field-help">A foto será ajustada e compactada antes de enviar. A câmera depende do celular e do navegador.</p>
 <x-preparo-foto id="imagem-usuario" />
 <details class="field-help"><summary>Formatos e limites</summary><p>Fotos de até 32 MB e 48 megapixels. JPG, PNG e WebP; HEIC/HEIF são convertidos. RAW/DNG exige uma prévia JPG incorporada compatível. Se não houver, escolha a versão JPG/HEIC ou use o modo padrão da câmera. O original não é enviado nem armazenado.</p></details>
 </div>
 <div class="imagem-modal-acoes imagem-modal-rodape"><button class="btn btn-secondary" id="imagem-usuario-cancelar" type="button">Cancelar</button><button class="btn btn-primary" id="imagem-usuario-salvar" type="submit" disabled>Salvar imagem</button></div>
 </form>
</dialog>
@php($g360Config = ['inicial'=>$imagemInicial,'acao'=>\App\Support\NavegacaoWeb::destino('perfil.imagem')])
<script type="application/json" id="imagem-usuario-config">@json($g360Config)</script>
<script src="{{ asset('js/imagem-usuario.js') }}?v={{ filemtime(public_path('js/imagem-usuario.js')) }}"></script>
