# ADR-0038 — O rodapé exibe literalmente "Gabarito360 - v0.1.0 - Homologação", com o ambiente derivado de APP_ENV

**Atualizado em:** 10/11/2026, por lucasgenial
**Data:** 2026-11-08
**Estado:** Aceita
**Decisor:** responsável (lucasgenial)
**Origem:** AGENTS.md, seção "Continuidade e publicação" ("Correção humana posterior e literal"); docs/187
**Afeta:** [FN-003](../funcionalidades/003-publicacao-e-homologacao/planejamento.md); [FN-075](../funcionalidades/075-tailwind-ui/planejamento.md)

Este documento registra uma decisão do responsável e as regras que dela decorrem.

## Contexto

O responsável quis ver o ambiente no rodapé, para distinguir homologação de produção.

## Decisão

Correção humana literal: rodapé **Gabarito360 - v0.1.0 - Homologação**. Isso supera a exibição do número isolado.

A fonte única continua `config/versao.php`. O ambiente deriva de `APP_ENV` sem alterar o `.env`: `staging` mostra Homologação, `production` mostra Produção e os demais mostram Desenvolvimento. Fase e SHA continuam internos.

Complemento: a busca de Turmas mantém o contrato da API (nome OU série). O rótulo do campo é "Nome ou série" e o resumo da busca começa com "Busca:".

## Consequências

- O rodapé não aparece no celular (ver [ADR-0044](0044-rodape-de-versao-oculto-no-celular.md)).

## Verificação

- O layout `apps/web/resources/views/layouts/app.blade.php` renderiza o rodapé com `config('versao.numero')` e `config('versao.ambiente')`.
