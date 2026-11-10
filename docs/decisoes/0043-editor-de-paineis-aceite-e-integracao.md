# ADR-0043 — O editor de painéis está aprovado e a integração real nos painéis Turmas e Turma está autorizada, só na homologação

**Atualizado em:** 10/11/2026, por lucasgenial
**Data:** 2026-11-09
**Estado:** Aceita
**Decisor:** responsável (lucasgenial)
**Origem:** AGENTS.md, seção "Continuidade e publicação" ("Aceite humano de 09/11/2026"); docs/191-editor-paineis-integracao.md
**Afeta:** [FN-054](../funcionalidades/054-paineis-editor/planejamento.md); [FN-053](../funcionalidades/053-paineis-turmas/planejamento.md)

Este documento registra uma decisão do responsável e as regras que dela decorrem.

## Contexto

O responsável avaliou a prévia local do editor e a aprovou.

## Decisão

Aceite humano: o editor e suas funcionalidades estão **aprovados**. A integração real nos painéis Turmas e Turma está autorizada, com revisão do planejamento, persistência no servidor por usuário, perfil, vínculo, painel e turma, compatibilidade e preservação dos dados e do histórico.

Valide, execute a CI, faça o merge e publique **somente na homologação**. A produção permanece fora do escopo.

## Consequências

- Esta aprovação supera os gates anteriores que restringiam o editor à prévia local ([ADR-0041](0041-editor-de-paineis-previa-local.md)).
- Preserve o quadro de colunas, os ajustes finos, a leitura mobile, os KPIs fixos e o catálogo autorizado.

## Verificação

- As preferências dos painéis têm rotas em `apps/api/routes/api.php` (`paineis/preferencias`, com `permissao:turma.ver` e limitação de taxa).
