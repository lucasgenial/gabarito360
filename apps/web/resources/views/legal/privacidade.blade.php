{{-- Prévia sem rota pública. A publicação depende da aprovação do texto jurídico. --}}
@extends('layouts.public')
@section('title', 'Aviso de privacidade — Prévia para revisão')
@section('robots', 'noindex, nofollow')
@section('content')
<section class="publico-status">
  <h1>Aviso de privacidade</h1>
  <x-aviso tipo="atencao">Prévia para revisão. Este documento ainda não foi publicado.</x-aviso>
  <p>O texto aprovado, a identificação da empresa e o contato do encarregado serão inseridos antes da publicação.</p>
</section>
@endsection
