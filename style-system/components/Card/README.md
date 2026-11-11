# Card

O card agrupa o conteúdo de uma região da tela: `.card` com `.card-pad`, ou o componente `x-cartao` com título e ações.

## Quando usar

- Use `.card.card-pad` como contêiner principal de uma lista ou formulário. Ponha `x-tabela` dentro dele (ADR-0022).
- Use `x-cartao` quando a região tem título (`h2`) e ações próprias.
- Não ponha borda colorida à esquerda nem degradê no card.

## Marcação de x-cartao

`x-cartao titulo="…"` com o slot `acoes` gera `section.g-cartao > .g-cartao-cab (h2 + ações) + .g-cartao-corpo`.

## Variantes

| Variante | Regra | Fonte |
|---|---|---|
| `.card` legado | fundo `surface`, borda `border-soft`, raio `radius-lg`, sombra `shadow-sm` (none); `.card-pad` padding 24 | `LEG:185-186` |
| `.card` TW2 (Turmas) | borda `border`, raio 8, sombra `shadow-sm` do Tailwind, padding 16 | `EST:110-111` |
| `x-cartao` | fundo `surface`, borda `border-soft`, raio `radius-lg`, margem superior 16; cabeçalho padding 16/20/0, título 18 px 700; corpo padding 16/20 | `LEG:900-903` |

## Regras por faixa

- Até 720 px, o card que contém `table.lista-cel` perde fundo e borda (`LEG:940`).
- O card ocupa a largura útil. Não aplique `max-width` em painel, lista ou formulário principal (ADR-0021).

ADRs: 0021, 0022.
