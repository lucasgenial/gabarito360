@push('styles')<link rel="stylesheet" href="@ativo('css/turma-detalhe.css')">@endpush
<nav class="tu1-abas" aria-label="Seções da turma" data-turma-abas data-secao-inicial="{{ $secaoInicial ?? 'visao-geral' }}">
  @foreach($secoes as $chave=>$rotulo)<a id="aba-{{ $chave }}" href="#{{ $chave }}" data-turma-aba="{{ $chave }}">{{ $rotulo }}</a>@endforeach
</nav>
@push('scripts')<script src="@ativo('js/turmas.js')" defer></script><script src="@ativo('js/turma-abas.js')" defer></script>@endpush
