@extends('layouts.app')
@section('title','Conferir leitura '.$dados['numero'])
@section('breadcrumb')
<a href="{{ route('correcao.show',$dados['prova']['id']) }}">Acompanhar correção</a><span class="sep">/</span><a href="{{ route('correcao.tentativas',$dados['prova']['id']) }}">Histórico de leituras do cartão-resposta</a><span class="sep">/</span><span>Leitura {{ $dados['numero'] }}</span>
@endsection
@push('styles')
<link rel="stylesheet" href="@ativo('css/leituras-resultados.css')">
@endpush

@section('content')
<x-pagina titulo="Conferir leitura {{ $dados['numero'] }}"><x-slot:sub>{{ $dados['aluno_nome'] }} · {{ $dados['prova']['titulo'] }}</x-slot:sub></x-pagina>
@if(session('sucesso'))<x-aviso tipo="sucesso">{{ session('sucesso') }}</x-aviso>@endif
@if($errors->any())<x-aviso tipo="erro">{{ $errors->first() }}</x-aviso>@endif
@if($dados['estado']==='aguardando_confirmacao')<x-aviso tipo="atencao">Este aluno já possui uma correção nesta prova. A correção vigente foi mantida. Confira a nova foto e as leituras antes de aplicar.</x-aviso>@endif
<div class="{{ $dados['tem_imagem']?'documento-estacao':'' }}">
<div class="card card-pad">
  <div class="leitura-cabecalho">
  <div class="row-between"><h2>Leitura {{ $dados['numero'] }}</h2><span class="badge badge-muted">{{ ['vigente'=>'Vigente','aguardando_confirmacao'=>'Aguardando confirmação','substituida'=>'Anterior','excluida'=>'Retirada','recusada'=>'Foto recusada','descartada'=>'Descartada'][$dados['estado']]??'Registro preservado' }}</span></div>
  <p>{{ $dados['autor_nome'] }} · {{ \Carbon\Carbon::parse($dados['ocorrido_em'],'UTC')->timezone('America/Sao_Paulo')->format('d/m/Y H:i') }}</p>
  @if($dados['tem_imagem'])<button type="button" id="doc-abrir" class="btn btn-secondary">Visualizar cartão-resposta</button>@endif
  <details><summary>Saiba mais sobre a preservação</summary><p>A foto recebida, as leituras e os eventos desta leitura ficam preservados. O resumo de integridade da imagem é {{ $dados['imagem_sha256']??'indisponível' }}. O arquivo é verificado ao abrir. O histórico não oferece edição de eventos finalizados.</p></details>
  </div>
  @include('correcao._leitura-questoes',['dados'=>$dados])
  @if($dados['pode_aplicar'])
  @php($pendencias=collect($dados['leituras'])->where('ambigua',true)->count())
  @if($pendencias)<x-aviso tipo="atencao">Esta leitura tem {{ $pendencias }} questões para revisão. Se aplicar esta leitura, a nova nota ficará pendente até resolver essas questões. O resultado anterior continuará preservado no histórico.</x-aviso>@endif
  <form method="POST" action="{{ route('correcao.tentativa.aplicar',$dados['id']) }}" class="tentativa-confirmacao">
    @csrf <x-revisao-acesso /><h3>Aplicar nova leitura</h3><p>O resultado vigente será atualizado com esta leitura. A foto, a nota e as revisões anteriores permanecem no histórico.</p>
    <label for="tentativa-motivo">Motivo da nova leitura</label><textarea id="tentativa-motivo" class="input" name="motivo" minlength="10" maxlength="500" required>{{ old('motivo') }}</textarea>
    <label><input type="checkbox" name="confirmar" value="1" required @checked(old('confirmar'))> Conferi a foto e autorizo aplicar esta leitura no lugar da correção vigente.</label>
    <button type="submit" class="btn btn-primary">Aplicar nova leitura</button>
  </form>
  @endif
  @if($dados['pode_descartar'])<form method="POST" action="{{ route('correcao.tentativa.descartar',$dados['id']) }}" class="tentativa-descartar">@csrf <x-revisao-acesso /><button type="submit" class="btn btn-secondary">Descartar leitura e manter correção atual</button></form>@endif
  <h3 class="tentativa-titulo-historico">Registro de ações</h3>
  @foreach($dados['eventos'] as $e)
    <details class="tentativa-evento"><summary>{{ ['vigente'=>'Leitura registrada','registro_anterior'=>'Registro anterior preservado','aguardando_confirmacao'=>'Nova foto aguardando confirmação','aplicada'=>'Nova leitura aplicada','revisada'=>'Revisão manual','vinculada'=>'Aluno vinculado','substituida'=>'Correção substituída','excluida'=>'Correção retirada','recusada'=>'Foto recusada','descartada'=>'Leitura descartada'][$e['acao']]??'Registro preservado' }} · {{ \Carbon\Carbon::parse($e['ocorrido_em'],'UTC')->timezone('America/Sao_Paulo')->format('d/m/Y H:i') }}</summary>
      <p>{{ $e['ator_nome'] }}{{ $e['motivo']?' · '.$e['motivo']:'' }}</p>
      @if(!empty($e['perfil']))<p>Perfil registrado: {{ \App\Support\Acesso::rotulo($e['perfil']) }}</p>@endif
      @if($e['nota'])<p>Nota preservada: {{ number_format((float)$e['nota']['nota_final'],1,',','.') }} · {{ $e['nota']['acertos'] }} acertos.</p>@endif
    </details>
  @endforeach
</div>
@if($dados['tem_imagem'])@include('correcao._documento',['documentoUrl'=>route('correcao.tentativa.imagem',$dados['id'],false),'documentoContexto'=>$dados['aluno_nome'].' · Leitura '.$dados['numero']])@endif
</div>
@endsection
