@extends('layouts.app')
@section('title', 'Selecionar contexto')
@section('content')
<x-pagina titulo="Selecionar contexto"><x-slot:sub>Escolha a instituição ou registro deste painel.</x-slot:sub></x-pagina>
<div class="section-card"><div class="section-body">
  <form method="GET" action="{{ route('painel') }}">
    <div class="field">
      <label for="contexto">Contexto da consulta</label>
      <select id="contexto" name="{{ $campo }}" class="input" required>
        <option value="">Selecione</option>
        @foreach($opcoes as $opcao)
        <option value="{{ $opcao['id'] }}">{{ $opcao['nome'] }}</option>
        @endforeach
      </select>
    </div>
    <button class="btn btn-primary" type="submit">Abrir painel</button>
  </form>
</div></div>
@endsection
