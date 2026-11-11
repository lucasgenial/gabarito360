# Ajuda no título

A ajuda no título transforma o título de um card, gráfico ou KPI num botão que abre uma explicação curta em popover.

## Quando usar

- Use quando o número ou o gráfico precisa de explicação (ADR-0028).
- Não use "Saiba mais" expansível como ajuda de card.
- Não transforme título de navegação em ajuda.
- Hoje o padrão existe só nos widgets de painel (`x-painel-widget` com `ajuda`). `x-cartao` e `x-indicador` não têm a opção (Divergências, item 26).

## Comportamento

- Abre por clique, toque, Enter ou Espaço (`popovertarget`).
- Fecha com Escape ou clique fora, sem roubar o foco.
- Oculta na impressão.

## Medidas

| Parte | Regra | Fonte |
|---|---|---|
| Botão | tipografia do título, altura 44, sublinhado pontilhado `border` (hover `accent`) | `painel-indicadores.css:1-8` |
| Foco | `outline: 2px solid accent; offset 3px` | `painel-indicadores.css:9` |
| Popover | fixo, 320 px, padding 16, raio `radius-md`, borda `border`, fundo `surface`, 14/1.5 | `painel-indicadores.css:10-17` |

## Acessibilidade

- `aria-controls`, `aria-describedby` e `aria-expanded` no botão.
- O popover tem `role="note"` e `aria-labelledby` no botão.

A prévia mostra o botão real (clique para abrir) e, abaixo, o conteúdo do popover em posição estática.

ADRs: 0028.
