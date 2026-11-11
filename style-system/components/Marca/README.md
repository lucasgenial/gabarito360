# Marca

O componente `x-marca` desenha a marca Gabarito360: o símbolo vetorial oficial (círculo e check) e o nome em texto, com "360" em azul.

## Quando usar

- No cabeçalho (sm), no carregamento (ícone 40 px), nos estados de tela (ícone lg) e nas páginas de acesso.
- Preserve o vetor oficial. Não desenhe símbolo aproximado. Não duplique a marca na lateral (doc 09 §2.2; ADR-0032).

## O que o consumidor fornece

- `variant`: `horizontal` (símbolo e nome) ou `icon` (só o símbolo).
- `size`: `sm`, `md` ou `lg`. `theme`: `auto`, `light` ou `dark`. `animated`: bool.

## Medidas

| Tamanho | Ícone | Nome | Gap | Fonte |
|---|---|---|---|---|
| sm | 32 | 18 | 8 | `MAR:15` |
| md | 36 | 22 | 9 | `MAR:14` |
| lg | 56 | 32 | 12 | `MAR:16` |

## Cor e fonte

- Nome em `g360-navy`; "360" em `g360-blue`; o "6" em G360 Albert Sans 800; o resto em G360 Figtree 800 (`MAR:1-2,19-21`).
- Tema escuro (`auto` ou `dark`): nome e círculo em `g360-white`. Na impressão, o círculo volta ao navy.
- `g360-blue` sobre `surface` claro tem 3,54:1. Use a marca só em tamanho de texto grande e negrito.

## Movimento

- Animação opcional de 1,05 s, sem repetir. Desligada com `prefers-reduced-motion`.

## Regras por faixa

- Até 440 px, o nome some em `.topo-marca` e `.lp-nav` (casca antiga e site público).

ADRs: 0027, 0032, 0034.
