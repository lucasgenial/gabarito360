# Página (x-pagina)

O componente `x-pagina` é o cabeçalho de toda tela da área logada: chamada opcional, título `h1`, subtítulo e ações.

## Quando usar

- Use no topo de toda tela da área logada, logo depois do breadcrumb.
- Tenha um único `h1` por tela.

## O que o consumidor fornece

- `titulo` (obrigatório), `subtitulo`, `chamada`, o slot `sub` e o slot `imagem` (avatar ou brasão).
- As ações no slot padrão. Botão só com ícone exige `aria-label` e `title`.

## Medidas

| Parte | Regra | Fonte |
|---|---|---|
| Bloco | flex com quebra, `justify-between`, gap 12, margem inferior 24; contêiner `pagina` | `EST:61` |
| Título `.page-title` | 24/32 600, `tracking-tight` | `EST:63` |
| Subtítulo `.page-sub` | 14/20 `muted` | `EST:64` |
| Chamada `.eyebrow` | 12/16 500 `accent` | `EST:65` |
| Ícones das ações | 16 px | `EST:125` |
| Com imagem | `.g-pagina-identidade`, gap 12 | `EST:67-69` |

## Ações progressivas (ADR-0034)

| Largura do contêiner `pagina` | O que muda |
|---|---|
| 36rem ou mais | rótulo completo ("Editar turma") |
| menos de 36rem | some o contexto repetido (`.acao-turma-contexto`) |
| menos de 24rem | some `.acao-turma-editar`; `.tu1r3-acao-editar` vira ícone de 44 px. Arquivar mantém o texto. |

ADRs: 0021, 0034.
