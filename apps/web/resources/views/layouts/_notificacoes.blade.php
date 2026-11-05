@php
  $notificacoes=[];
  foreach(['sucesso','erro','info'] as $tipo){
    $mensagem=session($tipo);
    if(is_string($mensagem)&&$mensagem!==''&& !($tipo==='erro'&&$errors->any()))$notificacoes[]=['tipo'=>$tipo,'mensagem'=>$mensagem];
  }
@endphp
<div id="g360-notificacoes" class="no-print" aria-label="Notificações"></div>
<script type="application/json" id="g360-notificacoes-iniciais">@json($notificacoes)</script>
<script src="{{ asset('js/notificacoes.js') }}?v={{ @filemtime(public_path('js/notificacoes.js')) }}" defer></script>
