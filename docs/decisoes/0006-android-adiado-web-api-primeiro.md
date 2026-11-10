# ADR-0006 — O aplicativo Android (MP-023) fica adiado; a equipe fecha antes as funcionalidades WEB e API

**Atualizado em:** 10/11/2026, por lucasgenial
**Data:** 2026-06-18
**Estado:** Aceita
**Decisor:** responsável (lucasgenial)
**Origem:** docs/13-roadmap.md, seção "Nota de Sequenciamento (pós MP-022, revisada 2026-06-18)"
**Afeta:** [FN-010](../funcionalidades/010-app-mobile/planejamento.md)

Este documento registra uma decisão do responsável e as regras que dela decorrem.

## Contexto

Após o MP-022 (relatórios), o responsável priorizou fechar as funcionalidades WEB e API. O aplicativo móvel dependia de contratos estáveis da API.

## Decisão

Executar os MPs 024 em diante antes do MP-023 (Android), apesar da numeração mais alta. Nenhum deles altera contrato existente da API de forma incompatível. Por isso, o início tardio do Android não gera retrabalho.

## Consequências

- Os diretórios `apps/android` e `apps/ios` não são criados enquanto a funcionalidade [FN-010](../funcionalidades/010-app-mobile/planejamento.md) estiver adiada.
- Contratos da API devem permanecer compatíveis para o futuro cliente móvel.

## Verificação

- O catálogo marca [FN-010](../funcionalidades/010-app-mobile/planejamento.md) como Adiada.
- `apps/` contém somente `api` e `web`.
