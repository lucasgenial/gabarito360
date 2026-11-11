# Lateral

A lateral `.lateral-app` é o menu principal do computador e do tablet, em duas larguras: expandida (256 px) e compacta (80 px).

## Quando usar

- A partir de 768 px. Até 767 px, use a Navegação inferior (ADR-0032).
- A lateral fica em fluxo: ela empurra o conteúdo. Não ponha backdrop, `inert` nem prisão de foco (ADR-0031).
- Não duplique a marca na lateral. A marca fica no cabeçalho (ADR-0032).

## Estados

| Estado | Regra | Fonte |
|---|---|---|
| Expandida | `--lateral: 256px`; item em linha, 40 px, 14 px 500, cor `muted`; sticky abaixo do cabeçalho; padding 16/12 | `EST:7,30-32` |
| Compacta | `html.menu-recolhido` → 80 px; ícone sobre rótulo, 64 px, 10/14; Meu espaço oculto | `EST:8,35,41` |
| Item ativo | texto `accent`, fundo `accent-light`, barra `inset 3px 0 #0588fc` (valor fixo igual a `g360-blue`) | `EST:34` |
| Hover | fundo `surface-2`, texto `fg` | `EST:33` |
| Escuro | fundo `surface` (#182234), texto #e7edf6, itens #c4d0e2 (10,22:1), hover e ativo branco sobre #ffffff18 | `EST:42-44` |

O tema escuro usa a superfície escura `surface` na lateral e no cabeçalho. Não troque essa superfície (ADR-0036).

## Comportamento

- Sem preferência gravada: compacta de 768 a 1279 px; expandida a partir de 1280 px (`layout-preferencias.js:4`).
- Recolher grava `g360-menu` = `recolhido` ou `aberto` e atualiza `aria-expanded` e o rótulo.

## Regras por faixa

- 768 a 1023 px: itens com 44 px.
- Até 767 px: oculta.

## Acessibilidade

- Cada link tem `title` e `aria-label` com o rótulo (a compacta mostra rótulo pequeno).
- O item atual tem `aria-current="page"`.

ADRs: 0031, 0032, 0033, 0036.
