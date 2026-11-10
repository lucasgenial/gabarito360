# ADR-0042 — A aba Distribuição mantém o quadro de colunas por seção; a grade visual pertence aos Ajustes finos

**Atualizado em:** 10/11/2026, por lucasgenial
**Data:** 2026-11-09
**Estado:** Aceita
**Decisor:** responsável (lucasgenial)
**Origem:** AGENTS.md, seção "Continuidade e publicação" ("Rodada seguinte da prévia"); docs/190
**Afeta:** [FN-054](../funcionalidades/054-paineis-editor/planejamento.md)

Este documento registra uma decisão do responsável e as regras que dela decorrem.

## Contexto

Uma resposta assíncrona pareceu autorizar uma edição visual WYSIWYG na aba Distribuição. O responsável reagiu de forma literal.

## Decisão

Citações do responsável: "Não! Era para continuar com o quadro de colunas!" e "Volte para o modelo anterior".

Mantenha o modelo anterior em **colunas** na aba Distribuição por seção, incluindo seções filhas embutidas. A grade visual pertence somente aos Ajustes finos.

- A raiz e cada seção são grades mistas de widgets e contêineres. Widgets avulsos compartilham a raiz, sem card central artificial.
- Padrão: três painéis configurados por perfil. O usuário pode nomear, criar ou reduzir a quantidade, sem apagar widgets ao remover um contêiner.
- Painéis sem widgets efetivamente visíveis não aparecem na leitura.
- O select Seção permite "Sem seção (avulso visível)" e "Criar nova seção".
- Abas com ícone e nome; títulos 20, 18 e 16 em negrito, conforme a referência.

## Consequências

- Esta correção supera a resposta assíncrona anterior que parecia autorizar WYSIWYG nessa aba.
- Preserve o catálogo, o escopo e os pins. Bloqueie ciclos e destino descendente.
- Continue somente local até a aprovação humana. Nenhuma autorização de deploy decorre dessa rodada.

## Verificação

- Os testes `scripts/tests/editor-paineis.test.mjs` e `scripts/tests/painel-grade.test.mjs` cobrem o editor.
