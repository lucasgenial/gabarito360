---
id: ADR-0028
titulo: Ajuda contextual no título de cards, gráficos e KPIs
estado: Aceita
substituida_por: []
data: 2026-11-08
afeta_funcionalidades: [FN-052, FN-053, FN-075]
afeta_caminhos: ["apps/web/resources/views/**"]
verificacao: revisao-ia
regras: []
agents_secao: Interface
agents_ordem: 80
agents_resumo:
  - "Ajuda contextual de cards e KPIs usa o título como botão."
---
# ADR-0028 — Orientações complementares de cards, gráficos e KPIs usam o próprio título como botão de ajuda contextual

**Atualizado em:** 10/11/2026, por lucasgenial
**Data:** 2026-11-08
**Estado:** Aceita
**Decisor:** responsável (lucasgenial)
**Origem:** AGENTS.md, seção "Área útil e composição das telas" (decisão de 08/11/2026); padrão aprovado no P3.2 (docs/182)
**Afeta:** [FN-052](../funcionalidades/052-dashboards-perfil/planejamento.md); [FN-053](../funcionalidades/053-paineis-turmas/planejamento.md); [FN-075](../funcionalidades/075-tailwind-ui/planejamento.md)

Este documento registra uma decisão do responsável e as regras que dela decorrem.

## Contexto

O padrão "Saiba mais" expansível ocupava espaço e variava entre telas. O P3.2 aprovou um padrão com o título como botão.

## Decisão

Use o próprio título do card, gráfico ou KPI como botão de ajuda contextual, conforme o padrão aprovado no P3.2. Abra por clique, toque, Enter ou Espaço. Feche com Escape ou clique fora, sem roubar o foco. Mantenha indicação visual sutil, foco visível, texto escapado e alternativa acessível.

## Consequências

- Prefira esse padrão ao "Saiba mais" expansível nas adequações dessas orientações.
- Não transforme títulos de navegação nem accordions de conteúdo operacional em ajuda por inferência.
- A decisão não autoriza migrar indiscriminadamente todos os collapses.

## Verificação

- O componente `orientacao.blade.php` implementa o padrão.
