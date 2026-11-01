@extends('layouts.auth', ['semMenuPublico' => true])
@section('title', 'Abrindo seu vínculo')
@section('content')
<main class="vinculo-pagina"><section class="card vinculo-card">
<h1>Abrindo seu vínculo</h1>
<p>Estamos preparando o acesso desta aba.</p>
<a class="btn btn-primary" href="{{ route('vinculos.index') }}">Escolher vínculo</a>
<noscript><p>Ative o JavaScript para manter o vínculo durante a navegação.</p></noscript>
</section></main>
<script>
let contexto = null;
try { contexto = sessionStorage.getItem('g360-contexto'); } catch (e) {}
if (contexto && /^[A-Za-z0-9]{48}$/.test(contexto)) {
  const destino = new URL(location.href); destino.searchParams.set('_ctx', contexto); location.replace(destino);
} else { location.replace(@json(route('vinculos.index'))); }
</script>
@endsection
