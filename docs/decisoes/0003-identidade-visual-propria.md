# ADR-0003 — O produto segue identidade visual própria, direção "Atual refinada", com tokens em um arquivo único

**Atualizado em:** 10/11/2026, por lucasgenial
**Data:** 2026-06-16
**Estado:** Substituída por [ADR-0026](0026-tailwindcss-como-base-visual.md) (parcial: a paleta Gabarito360 continua vigente)
**Decisor:** responsável (lucasgenial)
**Origem:** CLAUDE.md, seção "Design System"; docs/26-plano-identidade-visual.md
**Afeta:** [FN-070](../funcionalidades/070-identidade-visual/planejamento.md); [FN-075](../funcionalidades/075-tailwind-ui/planejamento.md)

Este documento registra uma decisão do responsável e as regras que dela decorrem.

## Contexto

O produto precisava de uma identidade própria, diferente do legado e de sistemas públicos de terceiros. A direção foi registrada com fontes, cores de acento e um arquivo único de tokens.

## Decisão

Valores registrados no CLAUDE.md:

| Item | Valor |
|---|---|
| Identidade | Própria, direção "Atual refinada" |
| Fonte principal | Figtree |
| Fonte de código | Roboto Mono |
| Cor accent | `#1d4f9e` |
| Cor de atenção | `#0b7f75` |
| Cor success | `#168821` |
| Arquivo de tokens | `apps/web/public/css/app.css` |

Todo componente visual segue os tokens desse arquivo.

## Consequências

- A paleta e os ativos de marca continuam vigentes.
- Em 08/11/2026, o responsável adotou o Tailwind como base visual e retirou a obrigação de reproduzir a estética anterior de fontes, espaçamentos, raios, sombras e composição (ver [ADR-0026](0026-tailwindcss-como-base-visual.md)).

## Verificação

- A paleta continua em `apps/web/resources/css/app.css` e nos tokens do tema.
- O CLAUDE.md ainda cita `apps/web/public/css/app.css` como arquivo de tokens. Confirme o caminho real antes de citá-lo (divergência registrada).
