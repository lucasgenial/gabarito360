@props(['titulo', 'completa' => false, 'modo' => 'lista'])
@php($completa = $completa && in_array($modo,['lista','selecao'],true))
{{-- Uma só árvore mantém formulários, contexto e permissões na tabela e nos cards. --}}
<div {{ $attributes->class(['tabela-conjunto'])->merge(['data-tabela' => '', 'data-modo' => $modo, 'data-colecao-completa' => $completa ? 'true' : 'false']) }}>
  @if($completa)
  @if($modo === 'selecao')
  <div class="tabela-filtros-selecao no-print" data-tabela-filtros-selecao hidden>
    <label>Buscar por nome ou sigla<input class="input" type="search" maxlength="100" data-tabela-busca aria-label="Buscar por nome ou sigla: {{ $titulo }}"></label>
    <label class="tabela-filtro-marcadas"><input type="checkbox" data-tabela-somente-selecionadas>Somente selecionadas</label>
    <button class="btn btn-secondary" type="button" data-tabela-filtrar>Filtrar</button>
  </div>
  <p class="tabela-selecao-aviso" data-tabela-selecao-alterada role="status" hidden>A seleção mudou. Use Filtrar para atualizar a lista de selecionadas.</p>
  @endif
  <div class="tabela-controles no-print" data-tabela-controles hidden>
    <label>Registros por página
      <select class="select" data-tabela-tamanho aria-label="Registros por página: {{ $titulo }}">
        <option value="20">20</option><option value="50">50</option><option value="100">100</option><option value="todos">Todos</option>
      </select>
    </label>
    <label>Ordenar
      <select class="select" data-tabela-ordem aria-label="Ordenação: {{ $titulo }}">
        <option value="original">Ordem original</option>
      </select>
    </label>
  </div>
  @endif
  @if($modo === 'matriz')<p class="tabela-dica no-print">Quadro comparativo: deslize para ver todas as colunas. No teclado, selecione o quadro e use as setas.</p>@endif
  <div class="tabela-area" @if($modo === 'matriz') tabindex="0" role="region" aria-label="{{ $titulo }}: quadro com rolagem horizontal" @endif>{{ $slot }}</div>
  @if($completa && $modo === 'selecao')<div class="tabela-selecao-vazia" data-tabela-selecao-vazia hidden><p>Nenhuma disciplina corresponde ao filtro. As seleções fora do filtro continuam preservadas.</p><button type="button" class="btn btn-secondary" data-tabela-limpar>Limpar filtros</button></div>@endif
  @if($completa)
  <div class="tabela-rodape no-print" data-tabela-rodape hidden>
    <p data-tabela-total role="status" aria-live="polite" aria-atomic="true"></p>
    <nav aria-label="Paginação: {{ $titulo }}">
      <button type="button" class="btn btn-secondary" data-tabela-anterior>Anterior</button>
      <span data-tabela-pagina></span>
      <button type="button" class="btn btn-secondary" data-tabela-proxima>Próxima</button>
    </nav>
  </div>
  @endif
</div>
