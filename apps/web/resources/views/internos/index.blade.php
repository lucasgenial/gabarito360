@extends('layouts.app')
@section('title', 'Acessos internos')
@section('breadcrumb')<a href="{{ route('painel') }}">Início</a><span class="sep">/</span><span>Acessos internos</span>@endsection
@section('content')
<x-pagina titulo="Acessos internos" subtitulo="Gerencie as atribuições de Suporte e Financeiro de outras contas." />
@if(session('sucesso'))<x-aviso tipo="sucesso">{{ session('sucesso') }}</x-aviso>@endif
@if(session('erro'))<x-aviso tipo="erro">{{ session('erro') }}</x-aviso>@endif
@if($errors->any())<x-aviso tipo="erro">{{ $errors->first() }}</x-aviso>@endif
<section class="card card-pad">
  <h2>Conceder acesso</h2>
  <form method="POST" action="{{ route('internos.store') }}">
    @csrf
    <div class="form-group"><label for="email">E-mail da conta existente</label><input class="input" id="email" name="email" type="email" required maxlength="254"></div>
    <div class="form-group"><label for="perfil">Perfil</label><select class="input" id="perfil" name="perfil"><option value="suporte">Suporte</option><option value="financeiro">Financeiro</option></select></div>
    <div class="form-group"><label for="fim">Válido até (opcional, fim do dia em Brasília)</label><input class="input" id="fim" name="fim_em" type="date"></div>
    <div class="form-group"><label for="motivo">Justificativa</label><input class="input" id="motivo" name="motivo" required minlength="5" maxlength="200"></div>
    <button class="btn btn-primary" type="submit">Conceder acesso</button>
  </form>
</section>
<x-pagina titulo="Atribuições gerenciáveis" />
@forelse($lista['data'] as $a)
<section class="card card-pad">
  <h3>{{ $a['nome'] }} · {{ \App\Support\Acesso::rotulo($a['perfil']) }}</h3>
  <form method="POST" action="{{ route('internos.update',$a['id']) }}">
    @csrf @method('PUT')
    <input type="hidden" name="versao_autorizacao" value="{{ $a['versao_autorizacao'] }}">
    <div class="form-group"><label for="situacao-{{ $a['id'] }}">Situação</label><select class="input" id="situacao-{{ $a['id'] }}" name="situacao"><option value="ativo" @selected($a['situacao']==='ativo')>Ativo</option><option value="suspenso" @selected($a['situacao']==='suspenso')>Suspenso</option></select></div>
    <div class="form-group"><label for="fim-{{ $a['id'] }}">Válido até (opcional, fim do dia em Brasília)</label><input class="input" id="fim-{{ $a['id'] }}" name="fim_em" type="date" value="{{ $a['fim_em'] ? \Illuminate\Support\Carbon::parse($a['fim_em'],'UTC')->timezone('America/Sao_Paulo')->format('Y-m-d') : '' }}"></div>
    <div class="form-group"><label for="motivo-{{ $a['id'] }}">Justificativa da alteração</label><input class="input" id="motivo-{{ $a['id'] }}" name="motivo" required minlength="5" maxlength="200"></div>
    <button class="btn btn-outline" type="submit">Salvar acesso de {{ $a['nome'] }}</button>
  </form>
</section>
@empty
<p>Nenhuma atribuição gerenciável encontrada.</p>
@endforelse
<nav aria-label="Paginação dos acessos">
@if($lista['current_page']>1)<a class="btn btn-outline" href="{{ route('internos.index',['page'=>$lista['current_page']-1]) }}">Anterior</a>@endif
<span>Página {{ $lista['current_page'] }} de {{ $lista['last_page'] }}</span>
@if($lista['current_page']<$lista['last_page'])<a class="btn btn-outline" href="{{ route('internos.index',['page'=>$lista['current_page']+1]) }}">Próxima</a>@endif
</nav>
@endsection
