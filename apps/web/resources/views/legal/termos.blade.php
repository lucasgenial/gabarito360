{{-- Prévia sem rota pública. A publicação depende da aprovação do texto jurídico. --}}
@extends('layouts.public')
@section('title', 'Termos de uso — Prévia para revisão')
@section('robots', 'noindex, nofollow')
@section('content')
<section class="publico-status">
  <h1>Termos de uso</h1>
  <x-aviso tipo="atencao">Prévia para revisão. Este documento ainda não foi publicado.</x-aviso>
  <p>O texto aprovado e os dados da empresa serão inseridos antes da publicação.</p>
</section>
@endsection
