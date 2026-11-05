@extends('layouts.app')
@section('title','Histórico de leituras do cartão-resposta')
@section('breadcrumb')
<a href="{{ route('correcao.show',$dados['prova']['id']) }}">Acompanhar correção</a><span class="sep">/</span><span>Histórico de leituras do cartão-resposta</span>
@endsection
@section('content')
<x-pagina titulo="Histórico de leituras do cartão-resposta"><x-slot:sub>{{ $dados['prova']['titulo'] }}</x-slot:sub></x-pagina>
<div class="card card-pad">
  <details><summary>Saiba mais sobre este histórico</summary><p>Cada nova foto identificada tem um registro de leitura próprio, referente à mesma aplicação da prova; não representa uma reaplicação. Repetições técnicas do mesmo envio não contam como outra foto. O histórico começa com os registros preservados a partir desta funcionalidade; registros apagados antes dela não podem ser reconstruídas.</p></details>
  @if(!$dados['tentativas'])<p class="page-sub">Nenhuma leitura arquivada neste filtro. As correções existentes continuam disponíveis em Acompanhar correção.</p>@endif
  <x-tabela class="tentativa-lista" titulo="Leituras do cartão-resposta">
    <table class="tabela-padrao"><thead><tr><th scope="col">Nome e dados</th><th scope="col">Ações</th></tr></thead><tbody>
      @foreach($dados['tentativas'] as $t)
      <tr><td class="tabela-principal" data-rotulo="Nome e dados"><strong>{{ $t['aluno_nome'] }}</strong><div>Leitura {{ $t['numero'] }} · {{ ['vigente'=>'Vigente','aguardando_confirmacao'=>'Aguardando confirmação','substituida'=>'Anterior','excluida'=>'Retirada','recusada'=>'Foto recusada','descartada'=>'Descartada'][$t['estado']] ?? 'Registro preservado' }}</div><div class="exam-meta">{{ $t['autor_nome'] }} · {{ \Carbon\Carbon::parse($t['ocorrido_em'],'UTC')->timezone('America/Sao_Paulo')->format('d/m/Y H:i') }}</div></td>
        <td class="tabela-acoes" data-rotulo="Ações"><a class="btn btn-secondary" href="{{ route('correcao.tentativa',$t['id']) }}">Conferir leitura</a></td></tr>
      @endforeach
    </tbody></table>
  </x-tabela>
  <div class="row-between"><span>{{ $dados['total'] }} registros · página {{ $dados['pagina'] }} de {{ $dados['ultima_pagina'] }}</span><div>
    @if($dados['pagina']>1)<a class="btn btn-secondary" href="{{ request()->fullUrlWithQuery(['page'=>$dados['pagina']-1]) }}">Anterior</a>@endif
    @if($dados['pagina']<$dados['ultima_pagina'])<a class="btn btn-secondary" href="{{ request()->fullUrlWithQuery(['page'=>$dados['pagina']+1]) }}">Próxima</a>@endif
  </div></div>
</div>
@endsection
