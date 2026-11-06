@props(['contexto','erroTroca'=>false])
@php
  $interno = \App\Support\Acesso::interno();
  $pessoal = ($contexto['espaco']['tipo'] ?? '') === 'pessoal';
  $nome = $interno ? 'Equipe Gabarito360' : ($pessoal ? 'Pessoal' : ($contexto['espaco']['nome'] ?? 'Acesso da conta'));
  $rotulo = $interno ? 'Área de acesso' : match($contexto['espaco']['tipo'] ?? '') {
    'escola'=>'Minha escola', 'rede'=>'Minha rede', 'nucleo'=>'Meu núcleo',
    'secretaria'=>'Minha secretaria', 'pessoal'=>'Tipo de espaço', default=>'Espaço ativo',
  };
@endphp
<details class="contexto-ativo">
  <summary class="contexto-resumo">
    <span class="contexto-resumo-titulo">Meu espaço</span>
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m9 6 6 6-6 6"/></svg>
  </summary>
  <dl class="contexto-dados">
    <div><dt>{{ $rotulo }}</dt><dd>{{ $nome }}</dd></div>
    <div><dt>Meu perfil</dt><dd>{{ \App\Support\Acesso::rotulo() }}</dd></div>
  </dl>
  @if($erroTroca)<p class="contexto-erro" role="status">Não foi possível verificar outros acessos. Atualize a página para tentar novamente.</p>@endif
</details>
