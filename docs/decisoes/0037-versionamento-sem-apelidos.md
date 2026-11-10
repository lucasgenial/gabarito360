# ADR-0037 — A versão usa número sem apelidos, com fonte única em config/versao.php

**Atualizado em:** 10/11/2026, por lucasgenial
**Data:** 2026-11-08
**Estado:** Substituída por [ADR-0038](0038-rodape-de-versao-com-ambiente.md) (a exibição só do número)
**Decisor:** responsável (lucasgenial)
**Origem:** AGENTS.md, seção "Continuidade e publicação" ("Versionamento definido durante TW2")
**Afeta:** [FN-003](../funcionalidades/003-publicacao-e-homologacao/planejamento.md); [FN-075](../funcionalidades/075-tailwind-ui/planejamento.md)

Este documento registra uma decisão do responsável e as regras que dela decorrem.

## Contexto

A aplicação precisava de uma numeração clara que não dependesse de apelidos nem do deploy.

## Decisão

Use numeração sem apelidos. A versão atual de desenvolvimento é 0.1.0. A fonte única é `apps/web/config/versao.php`. O rodapé exibia somente o número. Fase e SHA técnicos permanecem nos marcadores, recibos e na rastreabilidade.

| Faixa | Significado |
|---|---|
| 0.x | Desenvolvimento |
| 1.0.0 | Primeira versão estável |
| 1.x | Novos recursos compatíveis; correções no último número |
| 2.0.0 | Exige decisão de nova geração ou incompatibilidade; nunca por incremento automático de deploy ou etapa |

## Consequências

- Não promova a produção automaticamente por incrementar a versão.
- O formato de exibição mudou ([ADR-0038](0038-rodape-de-versao-com-ambiente.md)). A numeração e a fonte única continuam.

## Verificação

- `apps/web/config/versao.php` retorna `numero` igual a `0.1.0`.
