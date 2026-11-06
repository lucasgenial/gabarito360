@php
 $rascunho=!isset($aluno);
 $imagemInicial=['avatar_id'=>$aluno['avatar_id']??old('avatar_id'),'tem_foto'=>(bool)($aluno['tem_foto']??false),'imagem_versao'=>$aluno['imagem_versao']??1,'foto_url'=>$rascunho?null:\App\Support\NavegacaoWeb::destino('alunos.foto',['id'=>$aluno['id']])];
@endphp
<dialog id="imagem-aluno-modal" class="troca-contexto-modal imagem-modal" aria-labelledby="imagem-aluno-titulo" aria-describedby="imagem-aluno-orientacao">
 <div class="troca-contexto-cabecalho"><h2 id="imagem-aluno-titulo">{{ $rascunho ? 'Imagem do novo aluno' : 'Imagem de '.$aluno['nome'] }}</h2><button class="btn btn-secondary" id="imagem-aluno-fechar" type="button" aria-label="Fechar imagem do aluno">Fechar</button></div>
 <p id="imagem-aluno-orientacao">{{ $rascunho ? 'Escolha uma imagem. Ela será salva ao cadastrar o aluno; cancelar o cadastro descarta a seleção.' : 'Altere somente a imagem deste aluno. Os dados do cadastro e o histórico permanecem preservados.' }}</p>
 <p id="imagem-aluno-erro" class="field-error" role="alert" tabindex="-1" hidden></p>
 <p id="imagem-aluno-sucesso" role="status" hidden></p>
 <div id="imagem-aluno-preview" class="imagem-modal-preview" aria-label="Prévia da imagem"></div>
 <form id="imagem-aluno-form" method="POST" action="{{ $rascunho ? '#' : route('alunos.imagem', $aluno['id']) }}" enctype="multipart/form-data">
 @csrf <x-revisao-acesso />
 <fieldset class="imagem-modal-tipos"><legend class="sr-only">Escolha a imagem</legend><label><input type="radio" name="tipo" value="avatar"> Avatar</label><label><input type="radio" name="tipo" value="foto"> Foto</label></fieldset>
 <div id="imagem-aluno-avatares"><x-seletor-avatar :direto="true" titulo="Escolha um avatar" :nome="$aluno['nome']??old('nome','Novo aluno')" :avatar-id="$imagemInicial['avatar_id']" preview-id="imagem-aluno-avatar-preview" /></div>
 <div id="imagem-aluno-foto" class="field">
 <div id="imagem-aluno-origem" class="imagem-modal-acoes foto-origem"><button type="button" class="btn btn-secondary" data-camera>Tirar foto</button><button type="button" class="btn btn-secondary" data-arquivo>Escolher arquivo</button></div>
 <input hidden type="file" id="imagem-aluno-arquivo" name="foto" accept="image/jpeg,image/png,image/webp,image/heic,image/heif,.heic,.heif,.dng">
 <input hidden type="file" id="imagem-aluno-camera" accept="image/*" capture="user">
 <p class="field-help">A foto será ajustada e compactada antes de enviar. A câmera depende do celular e do navegador.</p>
 <x-preparo-foto id="imagem-aluno" />
 <details class="field-help"><summary>Formatos e limites</summary><p>Fotos de até 32 MB e 48 megapixels. JPG, PNG e WebP; HEIC/HEIF são convertidos. RAW/DNG exige uma prévia JPG incorporada compatível. Se não houver, escolha a versão JPG/HEIC ou use o modo padrão da câmera. O original não é enviado nem armazenado.</p></details>
 </div>
 <div class="imagem-modal-acoes imagem-modal-rodape"><button class="btn btn-secondary" id="imagem-aluno-cancelar" type="button">Cancelar</button><button class="btn btn-primary" id="imagem-aluno-salvar" type="submit" disabled>{{ $rascunho ? 'Usar esta imagem' : 'Salvar imagem' }}</button></div>
 </form>
</dialog>
@php($g360Config = ['inicial'=>$imagemInicial,'rascunho'=>$rascunho,'acao'=>$rascunho?null:\App\Support\NavegacaoWeb::destino('alunos.imagem',[$aluno['id']])])
<script type="application/json" id="imagem-aluno-config">@json($g360Config)</script>
<script src="{{ asset('js/imagem-aluno.js') }}?v={{ filemtime(public_path('js/imagem-aluno.js')) }}"></script>
