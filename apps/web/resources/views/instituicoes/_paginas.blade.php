@if($dados['paginacao']['ultima']>1)
<nav aria-label="Páginas" style="display:flex;gap:12px;align-items:center;margin-top:20px;flex-wrap:wrap;">
  @if($dados['paginacao']['atual']>1)<a class="btn btn-secondary" href="{{ route($rota,array_replace($f??[],['page'=>$dados['paginacao']['atual']-1])) }}">Anterior</a>@endif
  <span>Página {{ $dados['paginacao']['atual'] }} de {{ $dados['paginacao']['ultima'] }} · {{ $dados['paginacao']['total'] }} resultado(s)</span>
  @if($dados['paginacao']['atual']<$dados['paginacao']['ultima'])<a class="btn btn-secondary" href="{{ route($rota,array_replace($f??[],['page'=>$dados['paginacao']['atual']+1])) }}">Próxima</a>@endif
</nav>
@endif
