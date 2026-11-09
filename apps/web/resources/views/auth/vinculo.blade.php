@extends('layouts.auth', ['semMenuPublico' => true])
@section('title', !empty($internos) ? 'Escolha seu acesso' : 'Escolha seu vínculo')
@section('content')
<main class="vinculo-pagina" data-selecao-acesso>
  <a class="brand" href="{{ route('home') }}"><x-marca /></a>
  <section class="card vinculo-card" aria-labelledby="vinculo-titulo">
    <h1 id="vinculo-titulo">{{ !empty($internos) ? 'Escolha seu acesso' : 'Escolha seu vínculo' }}</h1>
    <p class="field-error" role="alert" tabindex="-1" data-entrada-erro hidden></p>
    <p>Olá, {{ session('auth_nome') }}. Selecione onde deseja trabalhar.</p>
    @if(session('sucesso'))<p role="status">{{ session('sucesso') }}</p>@endif
    @if(session('erro'))<p role="alert">{{ session('erro') }}</p>@endif
    @if($errors->any())<p role="alert">Escolha um vínculo válido.</p>@endif
    @if(!empty($internos))
      <h2>Equipe interna</h2>
      @foreach($internos as $a)
      <form method="POST" action="{{ route('vinculos.interno') }}" class="vinculo-opcao">
        @csrf <x-revisao-acesso />
        <input type="hidden" name="acesso_interno_id" value="{{ $a['id'] }}">
        <div><strong>Gabarito360</strong><p>{{ \App\Support\Acesso::rotulo($a['perfil']) }}</p></div>
        <button class="btn btn-primary" type="submit" aria-label="Entrar na equipe interna como {{ \App\Support\Acesso::rotulo($a['perfil']) }}">Entrar</button>
      </form>
      @endforeach
      @if($vinculos)<h2>Seus vínculos</h2>@endif
    @endif
    @if(!empty($legado))
      <form method="POST" action="{{ route('vinculos.legado') }}" class="vinculo-opcao">
        @csrf <x-revisao-acesso />
        <div><strong>Acesso da conta</strong><p>{{ \App\Support\Acesso::rotulo($legado['perfil']) }}</p></div>
        <button class="btn btn-primary" type="submit">Entrar</button>
      </form>
    @endif
    @forelse($vinculos as $v)
      <form method="POST" action="{{ route('vinculos.store') }}" class="vinculo-opcao">
        @csrf <x-revisao-acesso />
        <input type="hidden" name="vinculo_id" value="{{ $v['id'] }}">
        <div><strong>{{ $v['espaco']['nome'] }}</strong><p>{{ \App\Support\Acesso::rotulo($v['perfil']) }}</p></div>
        <button class="btn btn-primary" type="submit" aria-label="Entrar em {{ $v['espaco']['nome'] }} como {{ \App\Support\Acesso::rotulo($v['perfil']) }}">Entrar</button>
      </form>
    @empty
      @if(empty($internos) && empty($legado))
      <h2>Nenhum vínculo disponível</h2>
      <p>Você precisa de um vínculo ativo para acessar o sistema. Para acesso institucional, solicite ao responsável; para seu espaço pessoal, fale com o suporte.</p>
      <a class="btn btn-outline" href="{{ route('vinculos.index') }}">Verificar novamente</a>
      @endif
    @endforelse
    <form method="POST" action="{{ route('logout') }}">@csrf <x-revisao-acesso /><button class="btn btn-outline" type="submit">Sair da conta</button></form>
  </section>
</main>
@endsection
@push('scripts')<script src="@ativo('js/entrada-vinculo.js')"></script>@endpush
