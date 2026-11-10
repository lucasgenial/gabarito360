# ADR-0039 — Em todas as tabelas, a coluna de ações fica à direita, centralizada; colunas de dados ordenam

**Atualizado em:** 10/11/2026, por lucasgenial
**Data:** 2026-11-08
**Estado:** Aceita
**Decisor:** responsável (lucasgenial)
**Origem:** AGENTS.md, seção "Continuidade e publicação" ("Correção explícita posterior do responsável"); docs/188-tw3-acoes-direita.md
**Afeta:** [FN-074](../funcionalidades/074-tabelas-padrao/planejamento.md); [FN-075](../funcionalidades/075-tailwind-ui/planejamento.md)

Este documento registra uma decisão do responsável e as regras que dela decorrem.

## Contexto

A rodada 3 colocou Ações como primeira coluna, à esquerda. O responsável corrigiu a posição.

## Decisão

Correção explícita: em **todas** as tabelas, a coluna de ações fica à **direita**, como última coluna real no DOM. O título Ações e os botões ficam **centralizados** dentro dela.

Largura intrínseca ao conteúdo, sem limites fixos que cortem ou escondam botões. Grupos podem quebrar entre botões, nunca dentro de uma ação. Preserve SMALL, foco, ordem de leitura, permissões e rolagem regional quando indispensável.

Ordenação nas próprias colunas de dados, crescente e decrescente, exceto operações e seleção pura. Ordene antes da paginação em coleções completas. Em páginas parciais, identifique o escopo local sem inventar ordenação global nem API.

## Consequências

- Esta decisão supera a posição à esquerda da rodada 3 ([ADR-0033](0033-previa-rodada-3-contexto-icones-e-acoes.md)).
- A correção vale para tabelas. Os cards continuam com ações completas no rodapé esquerdo.
- O select permanece útil nos cards.

## Verificação

- Confira a última coluna do `x-tabela` e o docs/188.
