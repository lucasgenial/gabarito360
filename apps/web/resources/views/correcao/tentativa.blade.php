@extends('layouts.app')
@section('title','Conferir tentativa '.$dados['numero'])
@section('breadcrumb')
<a href="{{ route('correcao.show',$dados['prova']['id']) }}">Acompanhar correção</a><span class="sep">/</span><a href="{{ route('correcao.tentativas',$dados['prova']['id']) }}">Histórico de tentativas</a><span class="sep">/</span><span>Tentativa {{ $dados['numero'] }}</span>
@endsection
@section('content')
<x-pagina titulo="Conferir tentativa {{ $dados['numero'] }}"><x-slot:sub>{{ $dados['aluno_nome'] }} · {{ $dados['prova']['titulo'] }}</x-slot:sub></x-pagina>
@if(session('sucesso'))<x-aviso tipo="sucesso">{{ session('sucesso') }}</x-aviso>@endif
@if($errors->any())<x-aviso tipo="erro">{{ $errors->first() }}</x-aviso>@endif
@if($dados['estado']==='aguardando_confirmacao')<x-aviso tipo="atencao">Este aluno já possui uma correção nesta prova. A correção vigente foi mantida. Confira a nova foto e as leituras antes de aplicar.</x-aviso>@endif
<div class="{{ $dados['tem_imagem']?'documento-estacao':'' }}">
<div class="card card-pad">
  <div class="row-between"><h2>Tentativa {{ $dados['numero'] }}</h2><span class="badge badge-muted">{{ ['vigente'=>'Vigente','aguardando_confirmacao'=>'Aguardando confirmação','substituida'=>'Anterior','excluida'=>'Retirada','recusada'=>'Foto recusada','descartada'=>'Descartada'][$dados['estado']]??'Registro preservado' }}</span></div>
  <p>{{ $dados['autor_nome'] }} · {{ \Carbon\Carbon::parse($dados['ocorrido_em'],'UTC')->timezone('America/Sao_Paulo')->format('d/m/Y H:i') }}</p>
  @if($dados['tem_imagem'])<button type="button" id="doc-abrir" class="btn btn-secondary">Visualizar cartão-resposta</button>@endif
  <details><summary>Saiba mais sobre a preservação</summary><p>A foto recebida, as leituras e os eventos desta tentativa ficam preservados. O resumo de integridade da imagem é {{ $dados['imagem_sha256']??'indisponível' }}. O arquivo é verificado ao abrir. O histórico não oferece edição de eventos finalizados.</p></details>
  <h3>Leitura no recebimento da foto</h3>
  <x-tabela class="tentativa-lista" titulo="Leituras da tentativa" modo="compacta"><table class="tabela-padrao tabela-compacta"><thead><tr><th scope="col">Questão</th><th scope="col">Leitura</th></tr></thead><tbody>
  @forelse($dados['leituras'] as $q)<tr><td data-rotulo="Questão">{{ $q['numero_questao'] }}</td><td data-rotulo="Leitura">{{ !empty($q['ambigua'])?'Precisa de revisão':($q['alternativa']??'Em branco') }}</td></tr>@empty<tr><td colspan="2">Nenhuma leitura aprovada nesta foto.</td></tr>@endforelse
  </tbody></table></x-tabela>
  @if($dados['pode_aplicar'])
  @php($pendencias=collect($dados['leituras'])->where('ambigua',true)->count())
  @if($pendencias)<x-aviso tipo="atencao">Esta leitura tem {{ $pendencias }} questões para revisão. Se aplicar esta tentativa, a nova nota ficará pendente até resolver essas questões. O resultado anterior continuará preservado no histórico.</x-aviso>@endif
  <form method="POST" action="{{ route('correcao.tentativa.aplicar',$dados['id']) }}" class="tentativa-confirmacao">
    @csrf<h3>Aplicar nova tentativa</h3><p>O resultado vigente será atualizado com esta leitura. A foto, a nota e as revisões anteriores permanecem no histórico.</p>
    <label for="tentativa-motivo">Motivo da nova tentativa</label><textarea id="tentativa-motivo" class="input" name="motivo" minlength="10" maxlength="500" required>{{ old('motivo') }}</textarea>
    <label><input type="checkbox" name="confirmar" value="1" required> Conferi a foto e autorizo aplicar esta leitura no lugar da correção vigente.</label>
    <button type="submit" class="btn btn-primary">Aplicar nova leitura</button>
  </form>
  @endif
  @if($dados['pode_descartar'])<form method="POST" action="{{ route('correcao.tentativa.descartar',$dados['id']) }}" class="tentativa-descartar">@csrf<button type="submit" class="btn btn-secondary">Descartar tentativa e manter correção atual</button></form>@endif
  <h3 class="tentativa-titulo-historico">Registro de ações</h3>
  @foreach($dados['eventos'] as $e)
    <details class="tentativa-evento"><summary>{{ ['vigente'=>'Leitura registrada','registro_anterior'=>'Registro anterior preservado','aguardando_confirmacao'=>'Nova foto aguardando confirmação','aplicada'=>'Nova leitura aplicada','revisada'=>'Revisão manual','vinculada'=>'Aluno vinculado','substituida'=>'Correção substituída','excluida'=>'Correção retirada','recusada'=>'Foto recusada','descartada'=>'Tentativa descartada'][$e['acao']]??'Registro preservado' }} · {{ \Carbon\Carbon::parse($e['ocorrido_em'],'UTC')->timezone('America/Sao_Paulo')->format('d/m/Y H:i') }}</summary>
      <p>{{ $e['ator_nome'] }}{{ $e['motivo']?' · '.$e['motivo']:'' }}</p>
      @if(!empty($e['perfil']))<p>Perfil registrado: {{ \App\Support\Acesso::rotulo($e['perfil']) }}</p>@endif
      @if($e['nota'])<p>Nota preservada: {{ number_format((float)$e['nota']['nota_final'],1,',','.') }} · {{ $e['nota']['acertos'] }} acertos.</p>@endif
      @if($e['respostas'])<x-tabela class="tentativa-lista" titulo="Respostas preservadas" modo="compacta"><table class="tabela-padrao tabela-compacta"><thead><tr><th scope="col">Questão</th><th scope="col">Resposta registrada</th></tr></thead><tbody>@foreach($e['respostas'] as $q)<tr><td data-rotulo="Questão">{{ $q['numero_questao'] }}</td><td data-rotulo="Resposta registrada">{{ $q['ambigua']?'Revisão pendente':($q['alternativa']??'Em branco') }}</td></tr>@endforeach</tbody></table></x-tabela>@endif
      <p>{{ count($e['revisoes']) }} registros de revisão manual preservados neste momento.</p>
    </details>
  @endforeach
</div>
@if($dados['tem_imagem'])@include('correcao._documento',['documentoUrl'=>route('correcao.tentativa.imagem',$dados['id'],false),'documentoContexto'=>$dados['aluno_nome'].' · Tentativa '.$dados['numero']])@endif
</div>
@endsection
