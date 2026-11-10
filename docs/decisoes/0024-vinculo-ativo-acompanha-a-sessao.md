# ADR-0024 — O vínculo ativo acompanha a sessão autenticada, sem expiração própria; sessão irrecuperável encerra tudo

**Atualizado em:** 10/11/2026, por lucasgenial
**Data:** 2026-11-06
**Estado:** Aceita
**Decisor:** responsável (lucasgenial)
**Origem:** AGENTS.md, seção "Continuidade e publicação" (decisão explícita de 06/11/2026)
**Afeta:** [FN-015](../funcionalidades/015-vinculos-contexto/planejamento.md); [FN-018](../funcionalidades/018-seguranca-urls-sessao/planejamento.md); [FN-011](../funcionalidades/011-autenticacao-conta/planejamento.md)

Este documento registra uma decisão do responsável e as regras que dela decorrem.

## Contexto

A troca de vínculo introduziu o risco de uma sessão ficar sem contexto ativo válido. O responsável decidiu como tratar a continuidade e a recuperação.

## Decisão

Decisão explícita: o vínculo ativo acompanha a sessão autenticada, sem expiração por tempo independente. A seleção acontece no login ou na troca pelo controle de espaço e perfil.

Se a sessão ou seu contexto ativo se tornar irrecuperável, encerre toda a sessão Web e exija novo login.

## Consequências

- Não recupere o acesso pedindo apenas outro vínculo.
- Não crie novo prazo de sessão nesta entrega.
- Uma aba antiga após troca legítima continua bloqueada, sem encerrar a sessão nova e sem reenviar operações.

## Verificação

- Os testes `scripts/tests/contexto-sessao.test.mjs` e `scripts/tests/contexto-reset.test.mjs` cobrem o comportamento no navegador.
