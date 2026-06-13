# Aplicação Web e Design System V2

## Contrato de implementação

Cada HTML de `style-system/` deve ser convertido em rota/superfície funcional,
sem reinterpretar livremente sua composição. O Blade/Livewire pode compartilhar
componentes, mas o resultado final deve preservar pixels, conteúdo, fluxo e
comportamento do handoff.

## Fontes obrigatórias

- Estrutura e fluxo: HTMLs e `DESIGN-HANDOFF.md`.
- Tokens e componentes: `style-system/css/gov.css`,
  `docs/ui_token_gov_brasil.json` e `docs/SDGB.md`.
- Interações: `style-system/js/app.js` e controles visíveis em cada tela.

## Biblioteca mínima

Shell, govbar, header, navegação, menu de usuário, breadcrumb, tabs, botões,
campos, selects, upload, cards, KPIs, badges, tabelas, filtros, paginação,
gráficos, editor de gabarito, progresso, modal, drawer, toast, alertas e estados.

## Regras

- Tema claro inicial, independentemente do tema do sistema operacional.
- Tema escuro somente após ação explícita.
- Nenhum valor visual hardcoded fora dos tokens sem justificativa.
- Nenhum dado estático do protótipo pode aparentar ser dado real.
- Todos os controles devem funcionar com teclado e possuir rótulo acessível.
- Gráficos devem possuir resumo e tabela alternativa.

## Gate visual por tela

1. Rota e dados reais disponíveis.
2. Funcionalidade e estados completos.
3. Testes de autorização e acessibilidade aprovados.
4. Screenshots comparados nos nove viewports.
5. Diferenças justificadas e registradas na matriz.
