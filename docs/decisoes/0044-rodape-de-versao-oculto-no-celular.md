---
id: ADR-0044
titulo: Rodapé de versão oculto no celular
estado: Aceita
substituida_por: []
data: 2026-11-09
afeta_funcionalidades: [FN-073, FN-075]
afeta_caminhos: ["apps/web/resources/**"]
verificacao: revisao-ia
regras: []
agents_secao: Interface
agents_ordem: 101
agents_resumo:
  - "O rodapé de versão fica oculto no celular."
---
# ADR-0044 — O rodapé de versão não aparece no celular (até 767 CSS px) e não reserva espaço

**Atualizado em:** 10/11/2026, por lucasgenial
**Data:** 2026-11-09
**Estado:** Aceita
**Decisor:** responsável (lucasgenial)
**Origem:** AGENTS.md, seção "Continuidade e publicação" ("Correção humana de 09/11/2026")
**Afeta:** [FN-075](../funcionalidades/075-tailwind-ui/planejamento.md); [FN-073](../funcionalidades/073-layout-responsivo/planejamento.md)

Este documento registra uma decisão do responsável e as regras que dela decorrem.

## Contexto

O rodapé reservava espaço que prejudicava a navegação inferior do celular.

## Decisão

O rodapé de versão não aparece no celular (até 767 CSS px). Retire sua reserva de espaço, conservando a navegação inferior e a safe-area. Desktop e tablet (768 px ou mais) mantêm o rodapé e o formato definido.

## Consequências

- Não altere o número da versão por esta mudança.
- Não publique em produção por esta mudança.

## Verificação

- Procure a regra de mídia de 767 px que oculta `.rodape-app` no CSS do layout.
