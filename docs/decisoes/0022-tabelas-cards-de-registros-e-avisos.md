---
id: ADR-0022
titulo: Tabelas com x-tabela no card principal; listas viram cards no celular
estado: Aceita
substituida_por: []
data: 2026-11-04
afeta_funcionalidades: [FN-074, FN-075]
afeta_caminhos: ["apps/web/resources/views/**"]
verificacao: teste
regras: [R-TABELA-PADRAO]
agents_secao: Interface
agents_ordem: 50
agents_resumo:
  - "Tabelas usam `x-tabela`, dentro do card principal."
---
# ADR-0022 — Tabelas operacionais usam x-tabela dentro do card principal; listas viram cards no celular; avisos têm margem

**Atualizado em:** 10/11/2026, por lucasgenial
**Data:** 2026-11-04
**Estado:** Aceita
**Decisor:** responsável (lucasgenial)
**Origem:** AGENTS.md, seção "Tabelas, cards de registros e avisos" (padrão aprovado em 04/11/2026); docs/131 e docs/184
**Afeta:** [FN-074](../funcionalidades/074-tabelas-padrao/planejamento.md); [FN-075](../funcionalidades/075-tailwind-ui/planejamento.md)

Este documento registra uma decisão do responsável e as regras que dela decorrem.

## Contexto

Tabelas apareciam em cards separados ou com conteúdo encostado nas bordas. Avisos colavam nos elementos vizinhos. O responsável aprovou um padrão compartilhado.

## Decisão

**Tabelas.** Tabelas operacionais usam o componente `x-tabela`, integrado à área interna do card principal, com alinhamento comum ao título, filtros e formulário. Use `interna=true` somente quando o pai não fornece padding. Em card-pad, gráficos, details e diálogos, evite inset duplicado. O inset compartilhado é 24 px no desktop e 16 px no celular.

**Cards no celular.** Listas podem virar cards, preservando título, dados secundários, contexto e todas as ações autorizadas. O card principal continua visível. Duas colunas, Nome e dados mais Ações, são a escolha explícita do histórico de vínculos de áreas.

**Avisos.** Avisos compartilhados de sucesso, erro, atenção e informação têm `margin-block: 16px`. Em containers com gap, o gap separa os elementos. Preserve as cores semânticas e os roles `status` e `alert`.

## Consequências

- É proibido criar um card separado para a tabela ou deixar seu conteúdo full-bleed.
- Não imponha a estrutura de duas colunas a matrizes comparativas, seleção em lote ou relatórios que precisem de outras colunas.
- Preserve paginação e seleção. Nunca ative controles de coleção completa sobre uma página parcial.
- Mantenha impressão e PDF sem padding adicional que prejudique o A4.
- Revise o componente compartilhado antes de criar exceções locais.
- A posição da coluna de ações foi definida depois (ver [ADR-0039](0039-coluna-de-acoes-a-direita-e-ordenacao.md)).

## Verificação

- O componente fica em `apps/web/resources/views/components/tabela.blade.php`. Cerca de 45 views o usam.
