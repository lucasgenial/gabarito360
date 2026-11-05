@extends('layouts.auth', ['semMenuPublico' => true])
@section('title', 'Abrindo seu vínculo')
@section('content')
<main class="vinculo-pagina"><section class="card vinculo-card">
<h1>Escolha seu acesso</h1>
<p>Abra esta página pelo menu do seu vínculo para continuar.</p>
<a class="btn btn-primary" href="{{ route('vinculos.index') }}">Escolher vínculo</a>
</section></main>
<script>
let acesso = null;
try { sessionStorage.removeItem('g360-contexto'); acesso = sessionStorage.getItem('g360-acesso'); } catch (e) {}
if (acesso && /^(vinculo|pessoal|interno)-[1-9][0-9]{0,17}$/.test(acesso)) {
  const destino = new URL(location.href); destino.searchParams.delete('_ctx');
  destino.pathname = '/acessos/'+acesso+destino.pathname; location.replace(destino);
}
</script>
@endsection
