@push('styles')<link rel="stylesheet" href="{{ asset('css/turma-detalhe.css') }}?v={{ filemtime(public_path('css/turma-detalhe.css')) }}">@endpush
<nav class="tu1-abas" aria-label="Seções da turma" data-turma-abas data-secao-inicial="{{ $secaoInicial ?? 'visao-geral' }}">
  @foreach($secoes as $chave=>$rotulo)<a id="aba-{{ $chave }}" href="#{{ $chave }}" data-turma-aba="{{ $chave }}">{{ $rotulo }}</a>@endforeach
</nav>
@push('scripts')<script src="{{ asset('js/turmas.js') }}?v={{ filemtime(public_path('js/turmas.js')) }}" defer></script><script src="{{ asset('js/turma-abas.js') }}?v={{ filemtime(public_path('js/turma-abas.js')) }}" defer></script>@endpush
