---
id: ADR-0048
titulo: Tronco único de planejamento: plano mestre, governança e catálogo
estado: Aceita
substituida_por: []
data: 2026-11-09
afeta_funcionalidades: [todas]
afeta_caminhos: ["docs/funcionalidades/**", "docs/planejamento/**", "apps/**"]
verificacao: teste
regras: [R-CATALOGO, R-FN-NO-PR]
agents_secao: Planejamento
agents_ordem: 10
agents_resumo:
  - "O catálogo de funcionalidades e o plano mestre são o único tronco de planejamento. As regras estão em `docs/planejamento/01-governanca.md`. A CI verifica o catálogo com `scripts/verificar_funcionalidades.py`."
  - "Consulte o agente `gerente-projetos` antes de iniciar uma funcionalidade e ao fim de cada subfase. Toda funcionalidade tem `planejamento.md` e `casos-de-uso.md` aprovados antes da execução."
  - "Um plano que nasce numa entrega serve só a ela. O que vai além vira funcionalidade candidata no catálogo."
  - "Um módulo transversal bloqueia quem depende dele. Não crie versão local paralela."
---
# ADR-0048 — O planejamento tem um único tronco: o plano mestre, regido pela governança, com catálogo de funcionalidades

**Atualizado em:** 10/11/2026, por lucasgenial
**Data:** 2026-11-09
**Estado:** Aceita
**Decisor:** responsável (lucasgenial)
**Origem:** AGENTS.md e CLAUDE.md, seção "Planejamento centralizado (tronco único)"; docs/planejamento/01-governanca.md
**Afeta:** Todas as funcionalidades; [planejamento](../planejamento/00-plano-mestre.md)

Este documento registra uma decisão do responsável e as regras que dela decorrem.

## Contexto

Cada entrega criava planos próprios. Esses planos cresciam e passavam à frente do plano principal, sem decisão do responsável. O inventário de 09/11/2026 consolidou 77 funcionalidades.

## Decisão

Decisão do responsável: o planejamento tem um único tronco, o [plano mestre](../planejamento/00-plano-mestre.md), regido por [docs/planejamento/01-governanca.md](../planejamento/01-governanca.md).

1. Antes de iniciar qualquer funcionalidade, e ao terminar cada subfase, consulte o agente `gerente-projetos` (`.claude/agents/gerente-projetos.md`).
2. Toda funcionalidade, nova ou existente, tem `docs/funcionalidades/<NNN-slug>/planejamento.md` e `casos-de-uso.md` aprovados antes da execução.
3. Um plano que nasce durante uma entrega serve só para concluí-la. O que vai além entra no plano mestre como candidata e aguarda a priorização do responsável.
4. Um módulo transversal (por exemplo, a agenda) bloqueia as funcionalidades que dependem dele. Não crie versões locais paralelas.
5. Os documentos numerados `docs/NN-*.md` são histórico e evidência e não definem prioridade.

## Consequências

- Esta decisão substitui o planejamento por micro-passos ([ADR-0005](0005-micro-passos-e-definicao-de-concluido.md)).
- Quando um documento numerado diverge do plano mestre, vale o plano mestre.
- É proibido criar uma série de documentos que defina prioridade fora do plano mestre.

## Verificação

- `scripts/verificar_funcionalidades.py` verifica a consistência entre catálogo e `planejamento.md` na CI.
- O agente existe em `.claude/agents/gerente-projetos.md`.
