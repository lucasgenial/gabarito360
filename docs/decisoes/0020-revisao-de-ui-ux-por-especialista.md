---
id: ADR-0020
titulo: Revisão de UI/UX por especialista em toda mudança de interface
estado: Aceita
substituida_por: []
data: 2026-11-03
afeta_funcionalidades: [FN-070, FN-073, FN-074, FN-075, FN-076]
afeta_caminhos: ["apps/web/**", "mockups/**", "style-system/**"]
verificacao: revisao-ia
regras: []
agents_secao: Interface
agents_ordem: 10
agents_resumo:
  - "Toda mudança de interface exige consulta prévia e revisão final de um agente de UI/UX e Product Design."
  - "O implementador não aprova a própria tela."
---
# ADR-0020 — Toda mudança de interface exige aprovação do usuário ou de agente de UI/UX e Product Design, e consulta prévia

**Atualizado em:** 10/11/2026, por lucasgenial
**Data:** 2026-11-03
**Estado:** Aceita
**Decisor:** responsável (lucasgenial)
**Origem:** AGENTS.md, seção "UI, UX e Product Design" (regra solicitada em 03/11/2026 e decisão posterior)
**Afeta:** [FN-070](../funcionalidades/070-identidade-visual/planejamento.md); [FN-073](../funcionalidades/073-layout-responsivo/planejamento.md); [FN-074](../funcionalidades/074-tabelas-padrao/planejamento.md); [FN-075](../funcionalidades/075-tailwind-ui/planejamento.md); [FN-076](../funcionalidades/076-carregamento-estados/planejamento.md)

Este documento registra uma decisão do responsável e as regras que dela decorrem.

## Contexto

A interface do CI1-E foi reprovada na revisão inicial (docs/126). O responsável pediu um gate de qualidade de design que não dependesse do próprio agente implementador.

## Decisão

Toda alteração que afete interface, experiência, navegação, formulários ou textos de interação exige aprovação explícita do usuário **ou de um agente especializado em UI/UX e Product Design** antes de ser considerada concluída ou integrada.

Decisão posterior do responsável: antes de executar qualquer tarefa de design, consulte o agente especializado para definir o fluxo e a composição. A consulta prévia não substitui a revisão visual final.

## Consequências

- Antes de implementar ou refazer uma interface, revise o objetivo do usuário, o fluxo e os padrões existentes do produto.
- O agente implementador não aprova a própria interface. Delegue a revisão a um especialista ou apresente uma proposta concreta ao usuário. Não peça autorização a cada subdivisão técnica.
- A revisão final exige evidência visual atual no navegador, em desktop e celular, com as ações principais e os estados de erro. Registre por texto (ver [ADR-0049](0049-sem-prints-da-aplicacao.md)).
- Se a revisão reprovar, corrija e reenvie. Teste de código não aprova design. Não reutilize a aprovação de outra tela ou versão. A aprovação do CI1-D não aprova o formulário do CI1-E.
- A aprovação do especialista mede a qualidade de design. Ela não substitui autorizações do usuário para publicação ou acesso sensível.
- Use as skills disponíveis de Product Design, design critique e frontend-design quando pertinentes.

## Verificação

- O PR de interface registra o parecer do especialista, as pendências resolvidas e a aprovação.
- O modelo `.github/pull_request_template.md` pede o relato de revisão visual independente.
