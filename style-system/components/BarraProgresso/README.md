# Barra de progresso

A barra `.progress-track` mostra o andamento de uma tarefa com começo e fim, como as correções concluídas de uma prova.

## Quando usar

- Use com um texto que diga o número: "40 de 100 cartões corrigidos".
- Use `role="progressbar"` com `aria-valuenow`, `aria-valuemin`, `aria-valuemax` e `aria-label`.
- Para média versus meta, use `.media-meta-trilha` (12 px), não uma rosca (doc 09 §7).

## Variantes

Não existe regra global. A barra só tem estilo dentro destas telas:

| Escopo | Trilha | Preenchimento | Fonte |
|---|---|---|---|
| `.tela-correcao` | 8 px, `surface-2`, raio 99px, margem 18 | `accent` | `LEG:586-587` |
| `.tela-gabarito-editar` | 8 px, `surface-2`, margem 8 | `success` | `LEG:525-526` |
| `.lp-resultado` (público) | `accent-light` | `accent` | `LEG:1099-1100` |

A falta de regra global está registrada (Divergências, item 25). Numa tela nova, use o escopo da tela ou peça a regra global numa adequação.

## Movimento

- Largura com transição de .25 s.

ADRs: doc 09 §7.
