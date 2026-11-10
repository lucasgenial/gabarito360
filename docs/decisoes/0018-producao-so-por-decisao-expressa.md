---
id: ADR-0018
titulo: Produção só por decisão expressa do responsável
estado: Aceita
substituida_por: []
data: 2026-11-03
afeta_funcionalidades: [FN-003, FN-018, FN-046, FN-064]
afeta_caminhos: []
verificacao: processo
regras: []
agents_secao: Publicação
agents_ordem: 30
agents_resumo:
  - "A produção só recebe uma entrega por decisão expressa do responsável."
---
# ADR-0018 — A publicação em produção depende de decisão explícita do responsável, mesmo após CI, revisão e homologação

**Atualizado em:** 10/11/2026, por lucasgenial
**Data:** 2026-11-03
**Estado:** Aceita
**Decisor:** responsável (lucasgenial)
**Origem:** AGENTS.md, seção "Continuidade e publicação"; docs/127-h1-homologacao-isolada.md; docs/28, seção 1 "Limites"; docs/planejamento/00-plano-mestre.md, seção 6
**Afeta:** [FN-003](../funcionalidades/003-publicacao-e-homologacao/planejamento.md); [FN-018](../funcionalidades/018-seguranca-urls-sessao/planejamento.md); [FN-046](../funcionalidades/046-revisao-leituras/planejamento.md); [FN-064](../funcionalidades/064-pedidos-titulares/planejamento.md)

Este documento registra uma decisão do responsável e as regras que dela decorrem.

## Contexto

O ambiente `gabarito360.com.br` é produção e guarda dados reais. Até 02/11/2026, o documento 102 (planejamento L4-T6) impedia publicar microfases de L4 antes do fechamento completo. A homologação isolada criou outra forma de validar.

## Decisão

Regra do AGENTS.md: a publicação em produção "depende de decisão explícita do responsável no momento combinado, mesmo após CI, revisão do especialista e validação na homologação."

Concluir L4 ou obter parecer de design não autoriza promoção automática. Preserve os gabaritos e os demais dados existentes. Não inclua arquivos ou alterações preexistentes de outras tarefas nos commits da entrega.

## Consequências

- Não há deploy automático em produção. Incrementar a versão não promove a produção.
- Antes da janela, o responsável recebe a entrega concreta, o impacto e a necessidade de migração.
- O gate L4 do docs/102 ("não publicar microfases") convive com esta decisão. A decisão 2 do plano mestre propõe revogá-lo em favor do ADR-H1 e promover em blocos. A decisão está aberta.

## Verificação

- Confirme que a CI e os fluxos de publicação não contêm passo automático para produção.
- Consulte a decisão 2 em `docs/planejamento/00-plano-mestre.md`, seção 5.
