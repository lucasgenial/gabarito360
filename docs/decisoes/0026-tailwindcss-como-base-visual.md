# ADR-0026 — O TailwindCSS é a base visual efetiva; a paleta Gabarito360 permanece e a estética anterior deixa de ser requisito

**Atualizado em:** 10/11/2026, por lucasgenial
**Data:** 2026-11-08
**Estado:** Aceita
**Decisor:** responsável (lucasgenial)
**Origem:** AGENTS.md, seção "Área útil e composição das telas" (decisão posterior de 08/11/2026); docs/185-migracao-tailwind.md; docs/184-acoes-small-tabelas.md
**Afeta:** [FN-075](../funcionalidades/075-tailwind-ui/planejamento.md); [FN-070](../funcionalidades/070-identidade-visual/planejamento.md); [FN-074](../funcionalidades/074-tabelas-padrao/planejamento.md)

Este documento registra uma decisão do responsável e as regras que dela decorrem.

## Contexto

O produto mantinha dois sistemas de estilo em conflito: o CSS próprio e a estética definida em 16/06/2026. O responsável decidiu migrar para o Tailwind.

## Decisão

Adotar o TailwindCSS como base visual efetiva. Preserve a paleta Gabarito360. A estética anterior de fontes, espaçamentos, raios, sombras e composição não é requisito a reproduzir. Use a escala e os defaults do Tailwind e componentes compartilhados coerentes, sem conciliar permanentemente dois sistemas de estilos.

O Tailwind é um framework de utilitários, não um catálogo pronto de componentes. Avalie o conjunto das telas e fluxos, não botões isolados. Migre por fronteiras completas e retire os estilos antigos das famílias convertidas. Não deixe o reset atingir telas ainda fora da migração.

Botões contextuais de tabelas e cards seguem o tamanho SMALL. A altura das linhas é natural, conforme o maior conteúdo, com alvos de toque adequados e texto ou ícone escolhidos pela clareza de cada ação.

## Consequências

- Esta decisão supera a preservação estética anterior ([ADR-0003](0003-identidade-visual-propria.md)).
- Ela conserva requisitos funcionais, acessibilidade, área útil, responsividade, semântica, identidade e ativos de marca, permissões e integridade dos dados.
- Etapas TW1 a TW7 seguem em [FN-075](../funcionalidades/075-tailwind-ui/planejamento.md). As famílias completas TW3 a TW5 têm etapas próprias.

## Verificação

- `apps/web/package.json` lista `tailwindcss` 4 e `@tailwindcss/vite`.
- Confirme que as famílias migradas não carregam o CSS antigo.
