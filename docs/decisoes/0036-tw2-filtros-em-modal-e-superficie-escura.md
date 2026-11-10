---
id: ADR-0036
titulo: Filtros em modal e superfície escura da lateral e do cabeçalho
estado: Aceita
substituida_por: []
data: 2026-11-08
afeta_funcionalidades: [FN-036, FN-075]
afeta_caminhos: ["apps/web/resources/**"]
verificacao: revisao-ia
regras: []
agents_secao: Interface
agents_ordem: 94
agents_resumo:
  - "Filtros ficam em modal. A lateral e o cabeçalho mantêm a superfície escura."
---
# ADR-0036 — No TW2, os filtros ficam em modal e a lateral e o cabeçalho escuros mantêm a superfície antiga

**Atualizado em:** 10/11/2026, por lucasgenial
**Data:** 2026-11-08
**Estado:** Aceita
**Decisor:** responsável (lucasgenial)
**Origem:** AGENTS.md, seção "Continuidade e publicação" ("Correção humana durante TW2"); docs/187
**Afeta:** [FN-075](../funcionalidades/075-tailwind-ui/planejamento.md); [FN-036](../funcionalidades/036-organizacao-turma/planejamento.md)

Este documento registra uma decisão do responsável e as regras que dela decorrem.

## Contexto

Durante o TW2, o responsável corrigiu dois pontos da migração.

## Decisão

- Mantenha os filtros em modal, não em formulário inline. Na lista de Turmas, um botão de ícone abre Nome, Escola e Ano. Aplicar confirma a consulta. Cancelar e Escape descartam o rascunho e devolvem o foco.
- Preserve a superfície antiga da barra no tema escuro. A homologação computou cabeçalho e lateral em `rgb(24,34,52)`, com `--surface:#182234`.

## Consequências

- Isso supera o navy `#001534` específico da lateral, definido nas rodadas anteriores.
- Não altere o tema claro nem a paleta de ações.

## Verificação

- Procure `--surface:#182234` no CSS do tema escuro.
