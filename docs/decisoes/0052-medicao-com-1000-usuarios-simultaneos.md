# ADR-0052 — Toda medição de desempenho usa 1.000 usuários simultâneos, sem simulação

**Atualizado em:** 10/11/2026, por lucasgenial
**Data:** 2026-11-10
**Estado:** Aceita
**Decisor:** responsável (lucasgenial)
**Origem:** docs/planejamento/01-governanca.md, seção 6; docs/planejamento/00-plano-mestre.md (portão "Desempenho", FN-077); docs/planejamento/historico/2026-11-09-inventario-inicial.md
**Afeta:** [FN-077](../funcionalidades/077-desempenho/planejamento.md); [FN-007](../funcionalidades/007-escala-infraestrutura/planejamento.md)

Este documento registra uma decisão do responsável e as regras que dela decorrem.

## Contexto

Nenhuma medição das etapas F2 a F6 seguiu um padrão comum de carga. O inventário de 09/11/2026 já citava a regra como transversal. A governança de 10/11/2026 a registrou como regra em vigor. A citação literal original não consta nos arquivos revisados. O texto abaixo vem da governança.

## Decisão

Regra registrada: "Toda medição de desempenho usa 1000 usuários simultâneos", sem simulação. O plano mestre aplica a regra como portão Desempenho: quando a subfase mede desempenho, a medição usa 1000 usuários simultâneos.

A medição com 1000 usuários vem antes de qualquer abertura comercial ([FN-077](../funcionalidades/077-desempenho/planejamento.md)).

## Consequências

- Contas sintéticas só na homologação e gerador de carga fora da VM são a recomendação da decisão 8 do plano mestre. A decisão está aberta.
- Enquanto a decisão 8 estiver aberta, nenhuma medição F2 a F6 cumpre o padrão.
- O padrão convive com os fundamentos do ADR-195: método, release e volume sempre identificados.

## Verificação

- Consulte a decisão 8 em `docs/planejamento/00-plano-mestre.md`, seção 5.
