@php
  $parametros=request()->only(['from','tentativas_pagina','tentativa']);
  $urlHistorico=fn($mudancas=[])=>\App\Support\NavegacaoWeb::destino('resultados.historico',[$aluno['id'],$prova['id']]+array_filter(array_replace($parametros,$mudancas),fn($v)=>$v!==null&&$v!==''));
@endphp
  <details><summary>Saiba mais sobre as leituras</summary><p>Cada nova foto identificada tem um registro de leitura próprio, referente à mesma aplicação da prova; não representa uma reaplicação. Repetições técnicas do mesmo envio e revisões de questões não contam como novas leituras. Os registros anteriores à ativação desta funcionalidade começam com as evidências que puderam ser preservadas.</p></details>
  @if(!empty($historico['erro']))
    <x-aviso tipo="erro">Não foi possível carregar o histórico. O resultado acima continua disponível. <button type="button" data-historico-recurso="{{ json_encode($urlHistorico(['tentativa'=>null])) }}">Tentar novamente</button></x-aviso>
  @else
    <x-tabela class="tentativa-lista" titulo="Histórico de leituras do cartão-resposta do aluno">
      <table class="tabela-padrao"><thead><tr><th scope="col">Leitura e dados</th><th scope="col">Ações</th></tr></thead><tbody>
        @forelse($historico['tentativas'] as $t)
          <tr><td class="tabela-principal" data-rotulo="Leitura e dados">
            <strong>Leitura {{ $t['numero'] }} · {{ ['vigente'=>'Vigente','aguardando_confirmacao'=>'Aguardando confirmação','substituida'=>'Anterior','excluida'=>'Retirada','recusada'=>'Foto recusada','descartada'=>'Descartada'][$t['estado']]??'Registro preservado' }}</strong>
            <div class="exam-meta">{{ $t['autor_nome'] }} · {{ \Carbon\Carbon::parse($t['ocorrido_em'],'UTC')->timezone('America/Sao_Paulo')->format('d/m/Y H:i') }}</div>
            @if(!empty($t['motivo']))<div>Motivo: {{ $t['motivo'] }}</div>@endif
            @if(isset($t['nota_final']))<div>Nota preservada: {{ number_format((float)$t['nota_final'],1,',','.') }}</div>@endif
          </td><td class="tabela-acoes" data-rotulo="Ações"><button type="button" class="btn btn-secondary" data-historico-leitura="{{ $t['id'] }}" data-historico-recurso="{{ json_encode($urlHistorico(['tentativa'=>$t['id']])) }}" @if(($tentativa['id']??null)===$t['id']) aria-current="true" @endif>Conferir leitura</button></td></tr>
        @empty<tr><td colspan="2">Nenhuma leitura arquivada para este aluno nesta prova.</td></tr>@endforelse
      </tbody></table>
    </x-tabela>
    <div class="res-historico-rodape"><span>{{ $historico['total'] }} registros · página {{ $historico['pagina'] }} de {{ $historico['ultima_pagina'] }}</span><nav aria-label="Páginas do histórico de leituras">
      @if($historico['pagina']>1)<button type="button" class="btn btn-secondary" data-historico-recurso="{{ json_encode($urlHistorico(['tentativas_pagina'=>$historico['pagina']-1,'tentativa'=>null])) }}">Anterior</button>@endif
      @if($historico['pagina']<$historico['ultima_pagina'])<button type="button" class="btn btn-secondary" data-historico-recurso="{{ json_encode($urlHistorico(['tentativas_pagina'=>$historico['pagina']+1,'tentativa'=>null])) }}">Próxima</button>@endif
    </nav></div>
  @endif
  @if(($erroTentativa??false)||!empty($tentativa))
    <div class="res-tentativa-detalhe" id="tentativa-detalhe">
      @if($erroTentativa??false)<x-aviso tipo="erro">Não foi possível carregar esta leitura. <button type="button" data-historico-recurso="{{ json_encode($urlHistorico()) }}">Tentar novamente</button></x-aviso>
      @else
        <button type="button" class="btn btn-ghost" data-historico-voltar="{{ $tentativa['id'] }}" data-historico-recurso="{{ json_encode($urlHistorico(['tentativa'=>null])) }}">Fechar detalhes</button>
        @include('resultados._tentativa',['dados'=>$tentativa])
      @endif
    </div>
  @endif

<dialog id="historico-documento-modal" class="rev-cartao-modal no-print" aria-labelledby="historico-documento-titulo" aria-describedby="historico-documento-contexto">
  <header class="rev-modal-cabecalho"><div><h2 id="historico-documento-titulo">Cartão resposta preservado</h2><p id="historico-documento-contexto"></p></div><button type="button" class="btn btn-secondary" id="historico-documento-fechar" autofocus>Fechar</button></header>
  <div class="rev-documento-destino"><div id="historico-documento-leitor"></div></div>
</dialog>
