# Breadcrumb

O breadcrumb mostra o caminho da página acima de `x-pagina`, em texto pequeno e `muted`.

## Quando usar

- Em toda tela da área logada, exceto o Painel.
- Use a trilha dinâmica de `layouts/_breadcrumb.blade.php` (`<ol><li>`).

## Medidas

- 12/20 px, cor `muted`, gap 8, separador `/` com opacidade .5, margem inferior 16 (`EST:57-60`).
- O último item é `<span aria-current="page">`.

## Divergência

Algumas telas ainda usam `@section('breadcrumb')` com `<span class="sep">/</span>` manual (exemplo: `turmas/index.blade.php:7-9`). O CSS TW2 estiliza `ol` e `li`. Use só a trilha com `ol` em tela nova (Divergências, item 31).

ADRs: 0021.
