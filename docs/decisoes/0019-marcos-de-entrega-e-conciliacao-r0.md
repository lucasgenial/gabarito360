# ADR-0019 — Publique no fechamento validado de etapa de nível 1; subdivisões internas avançam sem pedir autorização

**Atualizado em:** 10/11/2026, por lucasgenial
**Data:** 2026-11-03
**Estado:** Aceita
**Decisor:** responsável (lucasgenial)
**Origem:** docs/117-r0-conciliacao-e-marcos-de-entrega.md; AGENTS.md, seção "Continuidade e publicação" (revisão do planejamento e fluxo de trabalho)
**Afeta:** [FN-042](../funcionalidades/042-duplicacao-provas/planejamento.md); [FN-025](../funcionalidades/025-transferencias-institucionais/planejamento.md); [FN-031](../funcionalidades/031-catalogo-instituicoes/planejamento.md); [FN-016](../funcionalidades/016-identidade-pessoal/planejamento.md); [FN-017](../funcionalidades/017-convites-cpf/planejamento.md); [FN-026](../funcionalidades/026-matricula-enturmacao/planejamento.md)

Este documento registra uma decisão do responsável e as regras que dela decorrem.

## Contexto

O responsável autorizou seguir os planos 115 e 116 mantendo suas regras de fases, subdivisões e atualização do servidor. A conciliação R0 registrou o inventário de código e as decisões de dependência. Ela não comprovou migração, homologação nem funcionalidade nova.

## Decisão

**Vocabulário:** fase é o agrupamento geral do planejamento. Etapa de nível 1 é uma entrega identificada (L4, CI1, CP1 e similares). Letras e números internos são organização técnica de nível 2 ou superior.

**Regras de execução e publicação:**

1. Execute as subdivisões internas em sequência, revise o planejamento quando necessário e mantenha testes, revisão, commits e push ou PR.
2. Não crie pedidos de autorização nem mensagens de início e fim para cada subdivisão.
3. Publique e atualize o servidor no fechamento validado de etapa de nível 1 ou fase. Nunca publique por concluir uma subdivisão.
4. Registre a dependência de uma etapa que não é publicável isoladamente. Componha uma release coerente. Não habilite metade de uma política de acesso.
5. Exija backup recuperável, migrações ensaiadas, revisão da release e comparação de conteúdo antes e depois do deploy.
6. Preserve gabaritos, questões, cartões, respostas, notas e imagens. Nenhuma autorização de continuidade permite excluí-los.
7. Consulte o responsável somente em decisão nova, ação fora do alcance ou impedimento que dependa dele.

**Decisões da conciliação R0-B:**

- Não faça alteração destrutiva. Acrescente estruturas, mapeie o legado, compare o conteúdo e só então troque leitores e escritores.
- Não preencha autorias, CPFs, escolas nem datas históricas por suposição. Divergência bloqueia o lote afetado e gera conciliação.
- A primeira entrega funcional foi CP1 (duplicação pedagógica), depois do contrato API, UX e dados e dos testes de autorização, independência e idempotência.
- Dependências: CI1 antes da seleção pessoal de instituição (PA1-A); ID1 antes da consulta de identidade e de VI1; DC1 antes da elegibilidade definitiva CP2; MT1 e TR1 antes das transferências escolares TE1.
- A execução da R0 foi sequencial e local. Nenhuma delegação paralela foi autorizada por aquela conciliação.

**Fluxo do AGENTS.md:** revise o planejamento antes de implementar cada etapa e apresente a tabela atualizada ao finalizar. Prossiga de forma autônoma pelas subdivisões internas autorizadas. Use o terminal para Git e GitHub. Prefira o Chrome do usuário para testes visuais.

## Consequências

- Concluir um documento ou um PR não equivale a fechar a etapa funcional.
- Merge não é deploy.
- Não publique a política antiga de transferências T5 como se implementasse os planos 115 e 116 (ver [FN-024](../funcionalidades/024-copias-entre-espacos/planejamento.md), substituída por [FN-025](../funcionalidades/025-transferencias-institucionais/planejamento.md)).
- O docs/117 é histórico. Seu conteúdo vigente está neste ADR e no plano mestre.

## Verificação

- Cada release na homologação gera recibo com backup e comparação de dados (portão "Homologação" do plano mestre).
- O catálogo registra as dependências entre [FN-016](../funcionalidades/016-identidade-pessoal/planejamento.md), [FN-017](../funcionalidades/017-convites-cpf/planejamento.md), [FN-025](../funcionalidades/025-transferencias-institucionais/planejamento.md), [FN-031](../funcionalidades/031-catalogo-instituicoes/planejamento.md), [FN-038](../funcionalidades/038-disciplinas-curriculo/planejamento.md) e [FN-042](../funcionalidades/042-duplicacao-provas/planejamento.md).
