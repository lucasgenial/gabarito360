@extends('layouts.auth', ['semMenuPublico' => true])
@section('title', 'Escolha seu vínculo')
@section('content')
<main class="vinculo-pagina">
  <a class="brand" href="{{ route('home') }}"><span class="logo">G360</span> Gabarito360</a>
  <section class="card vinculo-card" aria-labelledby="vinculo-titulo">
    <h1 id="vinculo-titulo">Escolha seu vínculo</h1>
    <p>Olá, {{ session('auth_nome') }}. Selecione onde deseja trabalhar nesta aba.</p>
    @if(session('sucesso'))<p role="status">{{ session('sucesso') }}</p>@endif
    @if(session('erro'))<p role="alert">{{ session('erro') }}</p>@endif
    @if($errors->any())<p role="alert">Escolha um vínculo válido.</p>@endif
    @forelse($vinculos as $v)
      <form method="POST" action="{{ route('vinculos.store') }}" class="vinculo-opcao" @if(count($vinculos) === 1 && $automatico) id="vinculo-unico" @endif>
        @csrf
        <input type="hidden" name="vinculo_id" value="{{ $v['id'] }}">
        <div><strong>{{ $v['espaco']['nome'] }}</strong><p>{{ \App\Support\Acesso::rotulo($v['perfil']) }}</p></div>
        <button class="btn btn-primary" type="submit" aria-label="Entrar em {{ $v['espaco']['nome'] }} como {{ \App\Support\Acesso::rotulo($v['perfil']) }}">Entrar</button>
      </form>
    @empty
      <h2>Nenhum vínculo disponível</h2>
      <p>Solicite ao responsável pela instituição um vínculo ativo para acessar o sistema.</p>
      <a class="btn btn-outline" href="{{ route('vinculos.index') }}">Verificar novamente</a>
    @endforelse
    <form method="POST" action="{{ route('logout') }}">@csrf<button class="btn btn-outline" type="submit">Sair da conta</button></form>
  </section>
</main>
<script>
try { sessionStorage.removeItem('g360-contexto'); } catch (e) {}
const unico = document.getElementById('vinculo-unico');
if (unico) unico.requestSubmit();
</script>
@endsection
