# Tabela padrão

A tabela `x-tabela` lista registros dentro do card principal, com ordenação, paginação e Ações na última coluna.

## Quando usar

- Use `x-tabela` em toda lista de registros. Não crie tabela própria com outro estilo (ADR-0022).
- Ponha a tabela dentro de `.card.card-pad`. Use `interna=true` só quando o pai não tem padding.
- Ponha Ações como última coluna, à direita, centralizada (ADR-0039).

## O que o consumidor fornece

- Props: `titulo` (vai para `caption.sr-only` e para os rótulos de controle), `completa`, `integrada` (padrão `true`), `interna`, `modo`.
- Cada `td` tem `data-rotulo` com o nome da coluna. O modo cards usa esse rótulo.
- A coluna principal recebe `td.tabela-principal`. As ações ficam em `td.tabela-acoes`.

## Modos

| Modo | Uso | Regra |
|---|---|---|
| `lista` (padrão) | registros | vira cards até 720 px |
| `selecao` | escolher registros | filtros "Somente selecionadas"; caixas de 20 a 22 px com `accent-color` |
| `matriz` | grade larga (questões × alunos) | `width: max-content`; área rolável com `tabindex=0` e `role=region`; nunca vira cards |
| `compacta` | listas curtas | `table-layout: auto`; continua tabela no celular, com rolagem |

## Medidas

| Parte | Regra | Fonte |
|---|---|---|
| Integrada | borda `border`, raio `radius-md`, controles e rodapé padding 12/16 | `TAB:6-8` |
| Cabeçalho | padding 12/16, fundo `surface-2`, texto `fg-2` 13 px 600 | `TAB:38` |
| Célula | padding 16, 14 px, borda `border-soft`; hover da linha `surface-2` | `TAB:37-41` |
| Ações | `width: 1%`; botões com 44 px e margem 3; o grupo quebra entre botões | `TAB:42-48` |
| Ordenação | `.tabela-ordenar` com seta por `aria-sort`; 44 px em toque | `TAB:96-101` |
| Foco | `outline: 3px solid accent; outline-offset: 3px` | `TAB:61,64` |

## Estados

- Vazio, sem resultado e erro são estados diferentes. Não mostre erro HTTP como lista vazia (doc 09 §5.1).
- A ordenação anuncia a mudança em `.tabela-ordenacao-status` (`role="status"`).

## Regras por faixa

- Até 720 px: modo cards (veja Tabela em cards).
- 721 a 1439 px: `table.lista-cel` rola dentro do card.
- Contêiner `tabela` com 64rem ou mais: ações em linha única (`TAB:90`).
- Turmas (TW2): células 8/12, cabeçalho 12 px 500, botões de ação 32 px (44 em toque).

ADRs: 0022, 0026, 0039.
