@props(['contexto'])
@php
  $interno = \App\Support\Acesso::interno();
  $nome = $interno ? 'Equipe Gabarito360' : $contexto['espaco']['nome'];
  $rotulo = $interno ? 'Acesso interno' : match($contexto['espaco']['tipo'] ?? '') {
    'escola'=>'Minha escola', 'rede'=>'Minha rede', 'nucleo'=>'Meu núcleo',
    'secretaria'=>'Minha secretaria', 'pessoal'=>'Meu espaço pessoal', default=>'Meu espaço',
  };
@endphp
<div class="contexto-ativo" aria-label="Contexto ativo">
  @if($nome !== $rotulo)<span class="contexto-ativo-rotulo">{{ $rotulo }}</span>@endif
  <strong class="contexto-ativo-nome">{{ $nome }}</strong>
  <span class="contexto-ativo-perfil">Meu perfil: <strong>{{ \App\Support\Acesso::rotulo() }}</strong></span>
</div>
