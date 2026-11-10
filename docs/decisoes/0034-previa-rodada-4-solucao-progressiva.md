# ADR-0034 — Rodada 4 aprovada: botões e ações se adaptam por largura disponível; logo oficial da homologação

**Atualizado em:** 10/11/2026, por lucasgenial
**Data:** 2026-11-08
**Estado:** Aceita
**Decisor:** responsável (lucasgenial)
**Origem:** AGENTS.md, seção "Continuidade e publicação" ("Rodada 4 aprovada pelo responsável"); docs/186, seção "Rodada4"
**Afeta:** [FN-075](../funcionalidades/075-tailwind-ui/planejamento.md); [FN-071](../funcionalidades/071-marca-documentos/planejamento.md); [FN-074](../funcionalidades/074-tabelas-padrao/planejamento.md)

Este documento registra uma decisão do responsável e as regras que dela decorrem.

## Contexto

A prévia precisava de uma regra para ações em larguras diferentes e de uma logo única.

## Decisão

Solução progressiva nos grupos da prévia, por largura disponível:

| Largura | Comportamento |
|---|---|
| Ampla | Mantém o rótulo completo. |
| Intermediária | Remove o contexto repetido. |
| Estreita | Filtro e Editar viram ícones. Arquivar mantém o texto. |

Não crie o tamanho `mp`. Conserve `pq` 32 px com alvo de toque de 44 px, nomes acessíveis, legendas e confirmação.

A logo usa a composição oficial da homologação: SVG, Figtree 800 e Albert Sans 800, medidas CSS 32, 18 e 8, com fontes locais e tema escuro. Não copie o zoom 1,25 da aba operacional.

## Consequências

- Somente a prévia até o aceite humano geral.
- Avalie desktop e mobile no Chrome antes de aplicar às telas reais.

## Verificação

- O componente `marca.blade.php` e os ativos de marca em `apps/web/public` implementam a logo.
