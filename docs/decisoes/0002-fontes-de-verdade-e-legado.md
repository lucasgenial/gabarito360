# ADR-0002 — Nenhum código nasce sem documentação aprovada; mockups e style system vencem a documentação; o legado só fornece regras

**Atualizado em:** 10/11/2026, por lucasgenial
**Data:** 2026-06-16
**Estado:** Aceita
**Decisor:** responsável (lucasgenial)
**Origem:** CLAUDE.md, seções "Regra Fundamental", "Prioridade das Fontes de Verdade" e "Uso do Legado"
**Afeta:** Todas as funcionalidades; [visão geral](../README.md)

Este documento registra uma decisão do responsável e as regras que dela decorrem.

## Contexto

O produto substitui um sistema legado. A equipe precisava impedir que o legado e a pressa dessem forma ao novo código. Também precisava de uma ordem clara para resolver conflitos entre documentos.

## Decisão

Regra fundamental literal: "Nenhum código deve ser criado sem documentação aprovada."

Toda funcionalidade tem origem rastreável em um mockup HTML do inventário ou em uma aprovação explícita posterior e documentada.

Ordem de prioridade das fontes: (1) mockups HTML em `mockups/`; (2) style system em `style-system/`; (3) documentação oficial em `docs/`; (4) regras do legado; (5) código do legado, só para consulta histórica. Em conflito vale: Mockup > Style System > Docs > Legado.

O diretório do legado serve para recuperar regras de negócio validadas, fluxos funcionais aprovados e decisões históricas documentadas. É proibido reutilizar arquitetura, banco, modelos, rotas, código frontend e código backend do legado.

## Consequências

- Antes de implementar uma tela, consulte o inventário dos mockups.
- Antes de criar endpoints, consulte a matriz de perfis e permissões.
- É proibido criar funcionalidades fora dos mockups sem aprovação.
- Desde 09/11/2026, a aprovação da funcionalidade também exige `planejamento.md` e `casos-de-uso.md` aprovados (ver [ADR-0048](0048-tronco-unico-de-planejamento.md)).

## Verificação

- Confirme que o PR cita o FN, o mockup ou a aprovação que o originou.
- Confirme que `scripts/verificar_funcionalidades.py` passa na CI.
