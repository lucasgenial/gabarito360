# ADR-0041 — A composição do editor de painéis em duas abas é apresentada primeiro como prévia local, sem aplicação nem publicação

**Atualizado em:** 10/11/2026, por lucasgenial
**Data:** 2026-11-09
**Estado:** Substituída por [ADR-0043](0043-editor-de-paineis-aceite-e-integracao.md)
**Decisor:** responsável (lucasgenial)
**Origem:** AGENTS.md, seção "Continuidade e publicação" ("Novo pedido humano"); docs/190-previa-editor-paineis.md
**Afeta:** [FN-054](../funcionalidades/054-paineis-editor/planejamento.md); [FN-053](../funcionalidades/053-paineis-turmas/planejamento.md)

Este documento registra uma decisão do responsável e as regras que dela decorrem.

## Contexto

O responsável pediu consolidar os cards-seção de Turmas e Turma em um editor de duas abas.

## Decisão

Apresente primeiro uma prévia **local**.

- A aba **Distribuição** controla a ordem das seções, a transferência e a ativação dos widgets e os títulos pessoais editáveis.
- A aba **Ajustes finos** usa GridStack somente dentro da seção atribuída.
- Os KPIs continuam fixos.
- A edição vale somente no desktop. No celular, mostre o que foi salvo no desktop ou o padrão do perfil, sem controles.
- A persistência futura é por usuário, perfil, vínculo, painel e turma, com autorização, versionamento e auditoria no servidor.
- Nesta entrega, use apenas dados e salvamento fictícios locais.

A nova composição supera a antiga proibição dessas preferências pessoais, sem conceder acesso a dados fora do catálogo autorizado.

## Consequências

- O usuário exige avaliação no Chrome e aprovação antes de qualquer aplicação ou publicação na homologação.
- Não integre silenciosamente na `main` nem publique em produção.
- O gate de prévia local foi superado pelo aceite de 09/11/2026 ([ADR-0043](0043-editor-de-paineis-aceite-e-integracao.md)).

## Verificação

- Veja o docs/190 como histórico da prévia.
