# Design System do Gabarito360

Esta pasta consolida a referência visual oficial do Gabarito360 para painel web, aplicativo Flutter, dashboards e demais interfaces.

## Fontes oficiais

1. [`style-system/`](../../style-system): contrato funcional e visual das telas web, incluindo composição, navegação, responsividade e interações.
2. [`docs/ui_token_gov_brasil.json`](../ui_token_gov_brasil.json): fonte implementável para cores, tipografia, espaçamentos, bordas, sombras, movimento, breakpoints e dimensões de componentes.
3. [`docs/SDGB.md`](../SDGB.md): fonte normativa para princípios visuais, identidade, comportamento, acessibilidade e catálogo obrigatório de componentes.

O mockup define o que a tela faz e como é composta. O token JSON prevalece para valores visuais implementáveis. A diretriz do SDGB continua obrigatória. Divergências ou tokens ausentes devem ser documentados antes da implementação; não devem ser resolvidos com estilos hardcoded silenciosos.

O tema claro é o padrão. O tema escuro é uma preferência opcional, nunca inferida automaticamente como primeira experiência.

## Documentos consolidados

- [`design-system.md`](design-system.md): fundamentos, tokens, estados e governança.
- [`tokens-web-r4.md`](tokens-web-r4.md): reconciliação congelada e espelho web dos tokens oficiais.
- [`componentes-web.md`](componentes-web.md): biblioteca e uso dos componentes Blade/Livewire.
- [`componentes-mobile.md`](componentes-mobile.md): biblioteca e uso dos componentes Flutter.
- [`dashboard.md`](dashboard.md): padrões de KPIs, gráficos, filtros e estados.
- [`dark-mode.md`](dark-mode.md): estratégia e regras do tema escuro.
- [`acessibilidade.md`](acessibilidade.md): requisitos WCAG 2.2 AA e critérios de aceite.
- [`roadmap-ui.md`](roadmap-ui.md): momento de desenvolvimento e relação com o plano executável.

## Aplicação obrigatória

- Painel web do MVP: Blade, Livewire e Tailwind conforme ADR-D009.
- App Android: Flutter e Material Symbols.
- Dashboards: componentes compartilhados, paleta oficial de gráficos e alternativas acessíveis.
- Relatórios em tela: tipografia, tabelas, filtros, estados e navegação do Design System.

O Design System não substitui Policies, regras de negócio, contratos da API ou validações. Componentes visuais devem refletir os estados fornecidos pelas camadas funcionais sem reimplementar autorização.

## Processo para novos padrões

1. Confirmar se o token ou componente já existe nas fontes oficiais.
2. Reutilizar o padrão existente sempre que possível.
3. Caso falte um token, registrar a necessidade e a justificativa em documentação antes de implementá-lo.
4. Validar modo claro, modo escuro, responsividade e acessibilidade.
5. Adicionar testes proporcionais à plataforma e ao risco.
