---
id: ADR-0056
titulo: O app atual é a referência visual; mockups/ e style-system/ são históricos
estado: Aceita
substituida_por: []
data: 2026-11-11
afeta_funcionalidades: [todas]
afeta_caminhos: ["mockups/**", "style-system/**", "apps/web/resources/views/**", "apps/web/public/css/**"]
verificacao: revisao-ia
regras: []
agents_secao: Interface
agents_ordem: 15
agents_resumo:
  - "A referência visual é o app atual: as views, os componentes e o CSS de `apps/web` na `main`, que a homologação publica. As pastas `mockups/` e `style-system/` são históricas."
  - "Todo mockup novo usa a casca, os componentes e o CSS reais de `apps/web/public/css/`. Ele não cria um visual paralelo. CSS próprio do mockup fica mínimo e usa só os tokens existentes."
---
# ADR-0056 — O app atual é a referência visual; `mockups/` e `style-system/` são históricos

**Data:** 2026-11-11
**Estado:** Aceita
**Decisor:** responsável (lucasgenial)
**Origem:** pedido do responsável em 11/11/2026, na revisão do mockup do Leitor360 (FN-079)
**Afeta:** todas as funcionalidades com interface; [ADR-0002](0002-fontes-de-verdade-e-legado.md) (ordem das fontes de interface); [09 Interface e design system](../arquitetura/09-interface-e-design-system.md)

## Contexto

O ADR-0002 pôs os mockups de `mockups/` e o style system de `style-system/` no topo das fontes de verdade. Desde junho de 2026, o app evoluiu por decisões registradas: menu lateral expandido e compacto, superfície escura, Tailwind, tabelas padrão, navegação inferior no celular e o redesenho TW2 (ADR-0021 a ADR-0044). Os arquivos de `mockups/` e `style-system/` não acompanharam essa evolução. Um mockup novo feito sobre eles nasce desatualizado. Isso aconteceu com o primeiro mockup do Leitor360.

## Decisão

O responsável decidiu: "Faça um arranjo para respeitar o system design atual. Pois os mockups já estão desatualizados visto o próprio app na homologação!"

1. A referência visual é o app atual: as views Blade, os componentes e o CSS de `apps/web` na `main`, que é o código publicado na homologação.
2. As pastas `mockups/` e `style-system/` passam a ser **históricas**. Elas continuam úteis para consultar o escopo e os textos originais de uma tela. Elas não decidem mais o visual.
3. Todo mockup de tela nova ou evoluída:
   - usa a casca real (cabeçalho, lateral, navegação inferior, rodapé);
   - usa os componentes reais (`x-pagina`, `x-aviso`, `x-tabela`, cards, botões, modais);
   - carrega o CSS real de `apps/web/public/css/`;
   - limita o CSS próprio ao que é novo, com os tokens existentes.
4. Em conflito sobre interface, vale esta ordem: (1) mockup novo aprovado pelo responsável e feito conforme o item 3; (2) app atual; (3) documentação em `docs/`; (4) `mockups/` e `style-system/` históricos.

## Consequências

- O ADR-0002 continua válido para o resto: documentação antes do código e o legado só como fonte de regras. A ordem das fontes de interface dele passa a ser a deste ADR.
- O revisor `ui-ux` confere o mockup novo contra o app atual, não contra `mockups/`.
- Um mockup novo fica em `mockups/` com o nome da funcionalidade (por exemplo, `mockups/leitor360.html`). Ele referencia o CSS de `apps/web/public/css/` por caminho relativo.
- A conferência visual segue o ADR-0049: sem prints no repositório, registro por texto.
- O documento [09 Interface e design system](../arquitetura/09-interface-e-design-system.md) e o CLAUDE.md devem citar esta ordem. A adequação fica registrada na lista gerada de adequações.

## Verificação

- O revisor `ui-ux` confirma, em cada mockup novo, que ele carrega o CSS de `apps/web/public/css/` e usa a casca e os componentes reais.
- O AGENTS.md gerado cita esta regra na seção Interface.
