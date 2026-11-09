{{--
  Arquivo: apps/web/resources/views/correcao/_camera.blade.php
  Função: Correção pela câmera do celular (OMR nativo, doc 18). Abre um visor ao vivo com
          as guias dos 4 quadrados pretos do cartão; cada canto fica verde quando o
          quadrado está no lugar e, com os 4 verdes e a mão firme, a foto é tirada sozinha.
          Sem câmera ao vivo (navegador antigo/permissão negada) cai na câmera do sistema.
          Usado no acompanhamento da prova e no modo só-câmera (aplicador).
          Espera: $prova e $alunos (vazio = sem vincular; a API não devolve nota ao aplicador).
--}}

<div class="card card-pad" id="camera" style="margin-top:16px;scroll-margin-top:90px;">
  <div style="display:flex;flex-wrap:wrap;gap:10px;">
    <button type="button" id="cam-abrir" class="btn btn-primary" style="flex:1 1 220px;justify-content:center;padding:16px;font-size:16px;"><x-icone nome="camera" /> Fotografar cartões</button>
    <input id="cam-input" type="file" accept="image/*" capture="environment" style="display:none;">
    <label for="lote-input" class="btn btn-secondary" style="flex:1 1 220px;justify-content:center;padding:16px;cursor:pointer;">Enviar fotos da galeria</label>
    <input id="lote-input" type="file" accept="image/*" multiple style="display:none;">
  </div>
  <p class="page-sub">Inclua a folha inteira e os quatro quadrados pretos.</p>
  <details><summary>Saiba mais sobre a foto e a leitura</summary><p>Não corte as bordas. Use boa luz e mantenha a câmera firme, de frente para a folha. As guias verdes indicam enquadramento; a qualidade será conferida antes de aceitar a foto. O QR identifica aluno e prova. Receber uma foto não significa concluir a leitura. Se já existir uma correção, ela será mantida até uma nova leitura ser conferida e aplicada.</p></details>
  <p id="envios-resumo" class="page-sub" role="status"></p>
  <ul id="envios" aria-label="Envios de cartões" aria-live="polite" style="list-style:none;padding:0;margin:8px 0 0;display:flex;flex-direction:column;gap:6px;"></ul>
</div>

{{-- Visor ao vivo com as guias dos cantos --}}
<div id="cam-tela" class="cam-tela" hidden>
  <canvas id="cam-canvas"></canvas>
  <video id="cam-video" playsinline muted style="display:none;"></video>
  <div class="cam-topo">
    <span id="cam-dica">Encaixe os 4 quadrados pretos nas guias</span>
    <button type="button" class="cam-btn-redondo" id="cam-fechar" aria-label="Fechar câmera">✕</button>
  </div>
  <div id="cam-ultimo" class="cam-ultimo"></div>
  <div class="cam-base">
    <button type="button" class="cam-btn-redondo" id="cam-luz" aria-label="Lanterna" style="visibility:hidden;"><x-icone nome="lanterna" /></button>
    <button type="button" class="cam-disparo" id="cam-disparo" aria-label="Tirar foto"></button>
    <label class="cam-auto"><input type="checkbox" id="cam-auto-chk" checked style="width:20px;height:20px;"> Auto</label>
  </div>
</div>

@push('scripts')
<script src="@ativo('js/cartoes-fila.js')"></script>
@php($g360Config = ['upload'=>\App\Support\NavegacaoWeb::destino('correcao.upload',[$prova['id']]),'envio'=>\App\Support\NavegacaoWeb::destino('correcao.envio',[$prova['id']]),'repetir'=>\App\Support\NavegacaoWeb::destino('correcao.envio.repetir',[$prova['id']]),'csrf'=>csrf_token(),'disciplina'=>$prova['disciplina']??'','alunos'=>collect($alunos)->map(fn($a)=>['id'=>$a['id'],'nome'=>$a['nome'],'turma'=>$a['turma_nome']??''])->values(),'chaveSessao'=>'g360:envios:'.hash('sha256',session()->getId()).':'.($prova['espaco_id']??'legado').':'.$prova['id'],'envioInicial'=>session('cartao_envio')])
<script type="application/json" id="correcao-camera-config">@json($g360Config)</script>
<script src="@ativo('js/correcao-camera.js')"></script>
@endpush
