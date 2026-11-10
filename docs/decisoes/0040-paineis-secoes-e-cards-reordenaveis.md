# ADR-0040 — Resultados por período e Agenda da turma entram em Resultados da turma; seções e cards centrais são reordenáveis

**Atualizado em:** 10/11/2026, por lucasgenial
**Data:** 2026-11-08
**Estado:** Aceita
**Decisor:** responsável (lucasgenial)
**Origem:** AGENTS.md, seção "Continuidade e publicação" ("Organização dos painéis"); docs/189-paineis-secoes-cards.md
**Afeta:** [FN-053](../funcionalidades/053-paineis-turmas/planejamento.md); [FN-054](../funcionalidades/054-paineis-editor/planejamento.md)

Este documento registra uma decisão do responsável e as regras que dela decorrem.

## Contexto

O painel individual da turma precisava de uma organização padrão e de personalização por seção, não só por widget.

## Decisão

No painel individual, Resultados por período escolar e Agenda da turma entram por padrão em Resultados da turma, abaixo de Estudantes. Além dos widgets, permita reordenar seções completas, com título e conteúdo, dentro do mesmo card central, e os cards centrais completos entre si.

Preserve os KPIs fixos, o catálogo e as permissões, os pins e a geometria relativa. Controles pequenos distintos pelo contexto e pelo nome acessível. Arraste ou use teclado e setas. Ofereça Salvar, Cancelar e Restaurar padrão. A edição vale somente em desktop e tablet amplo.

## Consequências

- As preferências ficam no mesmo salvamento versionado por usuário, vínculo, turma e painel.
- Nenhum histórico é apagado.

## Verificação

- A tela de edição dos painéis está em [FN-054](../funcionalidades/054-paineis-editor/planejamento.md). O comportamento do editor foi refinado nos ADRs 0041 a 0043.
