  <div class="leitura-cabecalho">
  <div class="row-between"><h3 id="tentativa-detalhe-titulo" tabindex="-1">Leitura {{ $dados['numero'] }}</h3><span class="badge badge-muted">{{ ['vigente'=>'Vigente','aguardando_confirmacao'=>'Aguardando confirmação','substituida'=>'Anterior','excluida'=>'Retirada','recusada'=>'Foto recusada','descartada'=>'Descartada'][$dados['estado']]??'Registro preservado' }}</span></div>
  <p>{{ $dados['autor_nome'] }} · {{ \Carbon\Carbon::parse($dados['ocorrido_em'],'UTC')->timezone('America/Sao_Paulo')->format('d/m/Y H:i') }}</p>
  @if($dados['tem_imagem'])<button type="button" class="btn btn-secondary" data-historico-imagem="{{ route('correcao.tentativa.imagem',$dados['id'],false) }}" data-historico-contexto="{{ $dados['aluno_nome'] }} · Leitura {{ $dados['numero'] }}">Ver cartão preservado</button>@endif
  <details><summary>Saiba mais sobre a preservação</summary><p>A foto recebida, as leituras e os eventos desta leitura ficam preservados. O resumo de integridade da imagem é {{ $dados['imagem_sha256']??'indisponível' }}. O arquivo é verificado ao abrir. O histórico não oferece edição de eventos finalizados.</p></details>
  </div>
  @include('correcao._leitura-questoes',['dados'=>$dados])
  @if($dados['pode_aplicar'])
  @if($errors->any())<x-aviso tipo="erro">{{ $errors->first() }}</x-aviso>@endif
  @php($pendencias=collect($dados['leituras'])->where('ambigua',true)->count())
  @if($pendencias)<x-aviso tipo="atencao">Esta leitura tem {{ $pendencias }} questões para revisão. Ao aplicar, você será levado à revisão das questões pendentes. O novo resultado ficará disponível após concluir essa revisão; o anterior permanecerá no histórico.</x-aviso>@endif
  <form method="POST" action="{{ route('correcao.tentativa.aplicar',$dados['id']) }}" class="tentativa-confirmacao">
    @csrf<input type="hidden" name="integrada" value="1"><input type="hidden" name="tentativas_pagina" value="{{ request('tentativas_pagina',1) }}"><h3>Aplicar nova leitura</h3><p>O resultado vigente será atualizado com esta leitura. A foto, a nota e as revisões anteriores permanecem no histórico.</p>
    <label for="tentativa-motivo">Motivo da nova leitura</label><textarea id="tentativa-motivo" class="input" name="motivo" minlength="10" maxlength="500" required>{{ old('motivo') }}</textarea>
    <label><input type="checkbox" name="confirmar" value="1" required @checked(old('confirmar'))> Conferi a foto e autorizo aplicar esta leitura no lugar da correção vigente.</label>
    <button type="submit" class="btn btn-primary">Aplicar nova leitura</button>
  </form>
  @endif
  @if($dados['pode_descartar'])<form method="POST" action="{{ route('correcao.tentativa.descartar',$dados['id']) }}" class="tentativa-descartar">@csrf<input type="hidden" name="integrada" value="1"><input type="hidden" name="tentativas_pagina" value="{{ request('tentativas_pagina',1) }}"><button type="submit" class="btn btn-secondary">Descartar leitura e manter correção atual</button></form>@endif
  <h3 class="tentativa-titulo-historico">Registro de ações</h3>
  @foreach($dados['eventos'] as $e)
    <details class="tentativa-evento"><summary>{{ ['vigente'=>'Leitura registrada','registro_anterior'=>'Registro anterior preservado','aguardando_confirmacao'=>'Nova foto aguardando confirmação','aplicada'=>'Nova leitura aplicada','revisada'=>'Revisão manual','vinculada'=>'Aluno vinculado','substituida'=>'Correção substituída','excluida'=>'Correção retirada','recusada'=>'Foto recusada','descartada'=>'Leitura descartada'][$e['acao']]??'Registro preservado' }} · {{ \Carbon\Carbon::parse($e['ocorrido_em'],'UTC')->timezone('America/Sao_Paulo')->format('d/m/Y H:i') }}</summary>
      <p>{{ $e['ator_nome'] }}{{ $e['motivo']?' · '.$e['motivo']:'' }}</p>
      @if(!empty($e['perfil']))<p>Perfil registrado: {{ \App\Support\Acesso::rotulo($e['perfil']) }}</p>@endif
      @if($e['nota'])<p>Nota preservada: {{ number_format((float)$e['nota']['nota_final'],1,',','.') }} · {{ $e['nota']['acertos'] }} acertos.</p>@endif
    </details>
  @endforeach
