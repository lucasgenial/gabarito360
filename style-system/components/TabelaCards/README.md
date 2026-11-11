# Tabela em cards

Até 720 px, a `x-tabela` nos modos `lista` e `selecao` vira uma pilha de cards, com rótulo à esquerda e valor à direita.

## Quando usar

- O modo cards é automático. Use a mesma marcação da Tabela padrão.
- Garanta `data-rotulo` em todo `td`. Sem ele, o card mostra o valor sem nome.
- Mostre todos os dados e todas as ações autorizadas no celular. Não esconda colunas (ADR-0022; doc 09 §5.3).

## Regras

| Parte | Regra | Fonte |
|---|---|---|
| Cabeçalho | `thead` vira `sr-only` | `TAB:66` |
| Linha | card em coluna, margem 12, padding 4/16, raio `radius-lg`, borda `border` | `TAB:66-89` |
| Célula | `data-rotulo` à esquerda (30% da largura; 40% em tabela integrada) e valor à direita | `TAB:66-89` |
| Principal | `.tabela-principal` sobe como título, 16 px | `TAB:66-89` |
| Ações | `.tabela-acoes` desce para o rodapé do card, alinhada à esquerda | `TAB:66-89` |
| Cards no desktop | `[data-apresentacao=cards]` mantém as ações no rodapé | `TAB:94`; `EST:145` |

## Regras por faixa

- 320 a 720 px: cards.
- 721 a 767 px: a casca ainda é de celular, mas a tabela volta a ser tabela, com rolagem no card. Isto é uma lacuna registrada (Responsivo).

## Acessibilidade

- O `caption` e o `thead` continuam no DOM para o leitor de tela.
- Botões de ação com 44 px.

ADRs: 0022, 0039.
