---
id: ADR-0005
titulo: Micro-passos (MP) com dependências e seis critérios de Concluído
estado: Substituída
substituida_por: [ADR-0048]
data: 2026-06-16
afeta_funcionalidades: []
afeta_caminhos: []
verificacao: processo
regras: []
---
# ADR-0005 — O trabalho avança em micro-passos (MP) com dependências e seis critérios de "Concluído"

**Atualizado em:** 10/11/2026, por lucasgenial
**Data:** 2026-06-16
**Estado:** Substituída por [ADR-0048](0048-tronco-unico-de-planejamento.md)
**Decisor:** responsável (lucasgenial)
**Origem:** docs/13-roadmap.md (MP-001 a MP-035); docs/14-plano-de-execucao.md ("Política de Definição de Concluído")
**Afeta:** [FN-001](../funcionalidades/001-fundacao-monorepo/planejamento.md) a [FN-077](../funcionalidades/077-desempenho/planejamento.md) (tradução MP para FN no catálogo)

Este documento registra uma decisão do responsável e as regras que dela decorrem.

## Contexto

O planejamento inicial dividiu o produto em micro-passos atômicos (MP-001 a MP-035). Cada MP tinha dependências, entregáveis, critérios de aceite e um commit esperado. A execução seguia a ordem do grafo de dependências.

## Decisão

Regras do roadmap e do plano de execução:

1. Nenhum MP começa sem as dependências concluídas.
2. Cada MP lista objetivo, entregáveis, critérios de aceite e commit esperado.
3. Um MP está **Concluído** quando: (a) todos os critérios de aceite estão marcados; (b) os testes unitários passam; (c) os testes de integração passam; (d) a view é fiel ao mockup; (e) existe um commit na convenção; (f) não há regressão nos MPs anteriores.

## Consequências

- Esta forma de planejar foi substituída pelo tronco único (plano mestre e catálogo FN) em 09/11/2026.
- Os documentos `docs/13` e `docs/14` ficam como histórico. A tradução MP para FN está no catálogo de funcionalidades.
- Os seis critérios de "Concluído" continuam úteis, mas os portões vigentes estão no plano mestre.

## Verificação

- Consulte o catálogo, coluna "Identificador antigo", para traduzir um MP em FN.
- Os portões vigentes estão em `docs/planejamento/00-plano-mestre.md`, seção 3.
