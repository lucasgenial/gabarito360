# Avatar

O avatar `x-avatar` identifica uma pessoa ou turma por imagem do catálogo, foto própria ou iniciais, sempre como elemento decorativo.

## Quando usar

- Em listas de alunos, no menu da conta e no cabeçalho de página com imagem (`x-pagina` com slot `imagem`).
- O avatar é decorativo (`aria-hidden="true"`). O botão ou o texto ao lado dá o nome.

## Variantes

| Item | Valor | Fonte |
|---|---|---|
| Tamanhos | `sm` 36, `md` 42, `lg` 72, `xl` 120 px (iniciais 13/14/24/36) | `LEG:1265-1268` |
| Cores (ID % 4) | azul (`accent-light`/`accent-dark`), verde (`atencao-light`/`atencao`), roxo (`purple-light`/`purple-fg`), neutro (`surface-2`/`fg-2`) | `LEG:1269-1272` |
| Catálogos | estudantes, educadores e brasões de turma; grade 6 × 6, IDs 1 a 36 | `LEG:1275,1407-1408` |
| Foto | `<img data-imagem-avatar>` carregada por JS; iniciais como reserva | `CMP/avatar:13` |

## Contraste

- Verde: `atencao` sobre `atencao-light` tem 4,21:1 no claro, abaixo de 4,5:1 para as iniciais de 13 e 14 px. Par mantido do código.

## Lacuna

- O ADR-0032 pede avatar de entidade com 64 px no desktop e 56 px no celular. Não há classe com esses valores.

A prévia mostra as iniciais. As imagens de catálogo (`images/avatares/*.webp`) não fazem parte deste design system.

ADRs: 0032, FN-066.
