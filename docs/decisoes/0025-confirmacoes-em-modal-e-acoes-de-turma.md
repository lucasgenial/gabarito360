# ADR-0025 — Confirmações de negócio usam modal da aplicação; Editar e Arquivar turma têm ícone e texto

**Atualizado em:** 10/11/2026, por lucasgenial
**Data:** 2026-11-07
**Estado:** Aceita
**Decisor:** responsável (lucasgenial)
**Origem:** AGENTS.md, seção "Continuidade e publicação" (decisão de 07/11/2026)
**Afeta:** [FN-036](../funcionalidades/036-organizacao-turma/planejamento.md); [FN-075](../funcionalidades/075-tailwind-ui/planejamento.md)

Este documento registra uma decisão do responsável e as regras que dela decorrem.

## Contexto

Diálogos nativos do navegador não seguem a identidade, o foco nem a acessibilidade da aplicação.

## Decisão

Confirmações de negócio usam modal da aplicação. As ações Editar turma e Arquivar ou Desarquivar turma têm ícone e texto, inclusive no celular.

## Consequências

- É proibido introduzir `window.confirm`, `alert` e `prompt` nativos.
- Preserve CSRF, versão, intenção explícita, Cancelar e Escape, foco e proteção contra duplo envio.

## Verificação

- Divergência: `apps/web/public/js/correcao-acompanhamento.js` ainda chama `confirm(` para um formulário de correção. Substitua por modal na próxima alteração dessa tela.
