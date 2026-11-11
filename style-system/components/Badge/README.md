# Badge

O badge `.badge` mostra a situação de um registro em texto curto, com cor por função.

## Quando usar

- Use em listas e tabelas para a situação: Ativa, Pendente, Arquivada.
- Sempre escreva a situação em texto. A cor só reforça (doc 09 §7).
- Em `.table`, o badge não quebra linha (`LEG:1012`).

## Variantes

12 px 600, padding 3/8, raio 4, `letter-spacing: .02em` (`LEG:189-192`).

| Classe | Fundo | Texto | Contraste |
|---|---|---|---|
| `.badge-success` | `success-light` | `success` | Fraco: 4,02:1 no claro, 3,39:1 no escuro. Par mantido do código. |
| `.badge-warn`, `.badge-amber` | `warn-light` | `warn-fg` | 6,79:1 claro, 9,48:1 escuro |
| `.badge-danger` | `danger-light` | `danger` | 4,66:1 claro; fraco no escuro (3,34:1) |
| `.badge-info` | `accent-light` | `accent-dark` | 8,89:1 claro, 7,35:1 escuro |
| `.badge-muted` | `surface-2` | `muted` | 5,40:1 claro, 6,49:1 escuro |
| `.badge-purple` | `purple-light` | `purple-fg` | 5,94:1 claro, 7,39:1 escuro |
| `.badge-dot` | — | — | bolinha de 6 px na cor do texto |

## Acessibilidade

- O badge é texto. Não use só a bolinha.
- `success` e `danger` diferem pouco em claridade. Use sempre a palavra, não só a cor.

ADRs: doc 09 §7.
