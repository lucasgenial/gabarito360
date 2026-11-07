@php($modelos=\App\Support\ModelosPaineis::para($paginaModelos, \App\Support\Acesso::perfil()??'', \App\Support\Acesso::permissoes()))
@if($modelos)
<section class="paineis-planejamento" aria-label="{{ \App\Support\Acesso::administrativo() ? 'Organização administrativa' : 'Planejamento e acompanhamento' }}">
  <h2>{{ \App\Support\Acesso::administrativo() ? 'Organização administrativa' : 'Planejamento e acompanhamento' }}</h2>
  <div class="paineis-modelos">
    @foreach($modelos as $modelo)
    <article class="card card-pad painel-modelo" data-painel-modelo="{{ $modelo['id'] }}">
      <div class="painel-modelo-cabecalho"><h3>{{ $modelo['titulo'] }}</h3><span class="badge">Em breve</span></div>
      <div class="painel-modelo-corpo"><span class="painel-modelo-icone" aria-hidden="true"><x-icone :nome="$modelo['icone']" /></span><p>{{ $modelo['finalidade'] }}</p></div>
    </article>
    @endforeach
  </div>
</section>
@endif
