@props(['contexto', 'interativo'=>false])
@php
  $interno = \App\Support\Acesso::interno();
  $nome = $interno ? 'Equipe Gabarito360' : $contexto['espaco']['nome'];
  $rotulo = $interno ? 'Acesso interno' : match($contexto['espaco']['tipo'] ?? '') {
    'escola'=>'Minha escola', 'rede'=>'Minha rede', 'nucleo'=>'Meu núcleo',
    'secretaria'=>'Minha secretaria', 'pessoal'=>'Meu espaço pessoal', default=>'Meu espaço',
  };
  $trocar = $interno ? 'Trocar acesso' : 'Trocar vínculo';
  $identificacao = $nome === $rotulo ? $nome : $rotulo.': '.$nome;
@endphp
@if($interativo)
<a class="contexto-ativo contexto-ativo-link" href="{{ route('vinculos.index',['trocar'=>1]) }}" data-sem-contexto
   aria-label="{{ $identificacao }}. Meu perfil: {{ \App\Support\Acesso::rotulo() }}. {{ $trocar }}">
@else
<div class="contexto-ativo" aria-label="Contexto ativo">
@endif
  @if($nome !== $rotulo)<span class="contexto-ativo-rotulo">{{ $rotulo }}</span>@endif
  <strong class="contexto-ativo-nome">{{ $nome }}</strong>
  <span class="contexto-ativo-perfil">Meu perfil: <strong>{{ \App\Support\Acesso::rotulo() }}</strong></span>
  @if($interativo)
    <span class="contexto-ativo-trocar">{{ $trocar }} <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m9 6 6 6-6 6"/></svg></span>
  @endif
@if($interativo)</a>@else</div>@endif
