# ADR-0027 — Uma página de prévia com componentes, propriedades e regras exige aceite humano antes da nova identidade nas telas operacionais

**Atualizado em:** 10/11/2026, por lucasgenial
**Data:** 2026-11-08
**Estado:** Aceita
**Decisor:** responsável (lucasgenial)
**Origem:** AGENTS.md, seção "Área útil e composição das telas" (complemento humano de 08/11/2026); docs/186-previa-componentes-tailwind.md
**Afeta:** [FN-075](../funcionalidades/075-tailwind-ui/planejamento.md)

Este documento registra uma decisão do responsável e as regras que dela decorrem.

## Contexto

A migração para o Tailwind mudaria todas as telas. O responsável quis ver e aprovar a identidade em uma página isolada antes de qualquer aplicação.

## Decisão

Complemento humano: apresente antes uma página isolada com componentes, propriedades e regras para aprovação do responsável. Não aplique a nova identidade às telas operacionais antes desse aceite.

Publique a prévia somente na homologação, com dados fictícios e sem operações nem gravação. Mantenha nomes próprios do projeto em pt-BR: tamanhos `pq`, `md`, `gd`, `xg` e largura `lg`, conforme a grafia exata escolhida. Não traduza APIs nem utilitários oficiais do framework.

## Consequências

- A aprovação técnica do especialista não substitui este gate humano específico.
- O gate foi satisfeito pelo aceite da rodada 4 (ver [ADR-0035](0035-aceite-da-previa-e-inicio-do-tw2.md)).

## Verificação

- A prévia está descrita no docs/186. Os tamanhos `pq`, `md`, `gd`, `xg` e `lg` aparecem nos componentes compartilhados.
