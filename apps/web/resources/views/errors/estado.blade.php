@extends('layouts.estado')
@php
  $codigo=(int)($codigo ?? (isset($exception) && method_exists($exception,'getStatusCode') ? $exception->getStatusCode() : 500));
  [$titulo,$mensagem]=match($codigo) {
    401=>['Entre novamente para continuar','Seu acesso precisa ser confirmado pelo login.'],
    403=>['Você não tem permissão para acessar esta página','Volte ao painel para consultar as opções disponíveis para seu perfil.'],
    404=>['Esta página não foi encontrada','Confira o endereço ou abra a página novamente pelo início.'],
    419=>['Esta página precisa ser atualizada','Entre novamente e confira os dados antes de continuar. Não reenvie o formulário anterior.'],
    429=>['Muitas tentativas seguidas','Aguarde um pouco antes de tentar novamente.'],
    503=>['Serviço temporariamente indisponível','Estamos com uma interrupção temporária. Tente acessar novamente em instantes.'],
    default=>$codigo>=500 ? ['Não foi possível concluir esta solicitação','Ocorreu um problema ao processar a solicitação. Tente acessar novamente em instantes.'] : ['Não foi possível abrir esta página','Volte ao início e confira as opções disponíveis.'],
  };
  $login=in_array($codigo,[401,419],true);
  $painel=request()->is('app/*');
  $inicio=$painel?'/app/painel':'/';
  $leitura=in_array(request()->method(),['GET','HEAD'],true);
  $repetir=$leitura && in_array($codigo,[500,503],true);
  $espera=isset($exception) && method_exists($exception,'getHeaders') ? ($exception->getHeaders()['Retry-After']??null) : null;
  if($codigo===429 && is_scalar($espera) && preg_match('/^[0-9]{1,4}$/',(string)$espera) && (int)$espera>0 && (int)$espera<=3600) $mensagem='Aguarde '.(int)$espera.' segundos antes de tentar novamente.';
  if(!$leitura && $codigo>=500) $mensagem='Confira se a operação foi concluída antes de tentar novamente. Não reenvie o formulário anterior.';
@endphp
@section('titulo', $titulo.' — Gabarito360')
@section('conteudo')
<main class="estado-pagina">
<x-estado-tela :titulo="$titulo" :mensagem="$mensagem" :codigo="$codigo">
@if($login)
  <a class="btn btn-primary" href="/login">Entrar novamente</a>
  <a class="btn btn-secondary" href="/">Voltar ao início</a>
@elseif($repetir)
  <a class="btn btn-primary" href="/{{ ltrim(request()->path(),'/') }}">Tentar novamente</a>
  <a class="btn btn-secondary" href="{{ $inicio }}">{{ $painel?'Voltar ao painel':'Voltar ao início' }}</a>
@else
  <a class="btn btn-primary" href="{{ $inicio }}">{{ $painel?'Voltar ao painel':'Voltar ao início' }}</a>
  @if($codigo>=500)<a class="btn btn-secondary" href="/fale-conosco">Falar com a gente</a>@endif
@endif
</x-estado-tela>
</main>
@endsection
