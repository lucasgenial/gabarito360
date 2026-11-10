---
id: ADR-0031
titulo: Menu principal em fluxo lateral no desktop
estado: Substituída
substituida_por: [ADR-0032]
data: 2026-11-08
afeta_funcionalidades: []
afeta_caminhos: []
verificacao: processo
regras: []
---
# ADR-0031 — O menu principal abre em fluxo lateral somente no desktop, sem sobreposição; o mobile mantém quatro atalhos e Mais

**Atualizado em:** 10/11/2026, por lucasgenial
**Data:** 2026-11-08
**Estado:** Substituída por [ADR-0032](0032-previa-rodada-2-menu-expandido-e-compacto.md)
**Decisor:** responsável (lucasgenial)
**Origem:** AGENTS.md, seção "Continuidade e publicação" ("Correção expressa do menu principal")
**Afeta:** [FN-075](../funcionalidades/075-tailwind-ui/planejamento.md); [FN-073](../funcionalidades/073-layout-responsivo/planejamento.md)

Este documento registra uma decisão do responsável e as regras que dela decorrem.

## Contexto

Uma resposta anterior sugeriu empurrar o conteúdo também no celular. O próprio responsável corrigiu o entendimento: falava do desktop.

## Decisão

Correção expressa: **somente o desktop** abre o menu em fluxo lateral, refazendo a largura disponível do conteúdo, sem sobreposição, backdrop, `inert` nem prisão de foco. Recolher devolve toda a largura. Mantenha aberto ao navegar entre seções até o recolhimento explícito.

Não altere o mobile. Preserve a estrutura da foto enviada: quatro atalhos inferiores e Mais. Não implemente menu acima do conteúdo. O avatar e a conta são independentes e aprovados.

## Consequências

- Apresentar os dois modelos, desktop e mobile, para avaliação antes de propagar às telas reais.
- A rodada 2 substituiu o recolhimento "sem coluna" desta rodada por um menu que alterna entre expandido e compacto.

## Verificação

- Superada em parte. Consulte o [ADR-0032](0032-previa-rodada-2-menu-expandido-e-compacto.md) para o comportamento vigente.
