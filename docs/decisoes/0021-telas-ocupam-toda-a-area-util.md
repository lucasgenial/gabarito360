# ADR-0021 — As telas ocupam toda a área útil do viewport, sem max-width fixo que deixe faixa vazia

**Atualizado em:** 10/11/2026, por lucasgenial
**Data:** 2026-11-03
**Estado:** Aceita
**Decisor:** responsável (lucasgenial)
**Origem:** AGENTS.md, seção "Área útil e composição das telas" (decisão explícita do responsável em 03/11/2026)
**Afeta:** [FN-073](../funcionalidades/073-layout-responsivo/planejamento.md); [FN-075](../funcionalidades/075-tailwind-ui/planejamento.md)

Este documento registra uma decisão do responsável e as regras que dela decorrem.

## Contexto

Telas largas mostravam painéis estreitos com faixas vazias nas laterais. O responsável pediu aproveitamento total da área disponível.

## Decisão

Decisão explícita: as telas da aplicação ocupam toda a área útil do viewport, em largura e altura, descontados menu, cabeçalho, rodapé e navegação móvel. Use o layout compartilhado e superfícies de trabalho com largura 100%.

## Consequências

- Não use `max-width` fixo em painéis, formulários principais ou listas que deixe faixa vazia na tela ampla.
- Margens responsivas e limites de leitura de parágrafos são permitidos. Controles, diálogos e imagens mantêm limites próprios quando necessários.
- Grupos de trabalho seguem empilhados, com campos responsivos dentro deles.
- Valide desktop amplo, menu recolhido e celular, sem overflow nem ação encoberta pelo rodapé.
- Toda mudança visual continua sujeita à aprovação do [ADR-0020](0020-revisao-de-ui-ux-por-especialista.md).

## Verificação

- Revise o CSS de layout em `apps/web/resources/css/estrutura-app.css` e o componente `pagina.blade.php`.
- Teste a tela em largura ampla com o menu recolhido.
