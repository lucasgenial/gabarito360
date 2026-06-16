# Registro de Execucao - Web V2 (shell autenticado gov.br)

## Status: Em execucao (fatia 2 da V2-03)

Esta fatia implementa o **shell autenticado compartilhado** previsto em
[`telas/_shell.md`](telas/_shell.md), reutilizando a fundacao gov.br ja portada
na fatia de login e preservando os contratos funcionais do backend V2.

## Objetivo

Unificar as telas autenticadas do portal e do painel administrativo em uma base
visual comum com:

- govbar institucional;
- header azul gov.br com marca, navegacao principal, badge de contexto,
  alternancia de tema e menu da conta;
- breadcrumb padrao;
- responsividade sem rolagem horizontal;
- compatibilidade com as views Blade ja existentes, evitando reescrever todas as
  telas nesta etapa.

## Fontes consultadas

- `style-system/dashboard.html`
- `style-system/dashboard-admin.html`
- `style-system/dashboard-coordenador.html`
- `style-system/js/app.js`
- `docs/v2/02-inventario-funcional-mockup.md`
- `docs/v2/15-matriz-rastreabilidade.md`
- `docs/v2/telas/_shell.md`
- `docs/design/design-system.md`
- `docs/design/componentes-web.md`
- `docs/ui_token_gov_brasil.json`
- `docs/SDGB.md`

## Estrategia

1. Consolidar o layout autenticado em torno do shell gov.br ja usado em
   `layouts/app.blade.php`.
2. Adaptar `layouts/admin.blade.php` para o mesmo padrao visual e de navegacao,
   porque hoje a maior parte das telas web ainda depende dele.
3. Complementar `resources/css/app.css` com classes estruturais usadas pelas
   views atuais (`page-heading`, `content-grid`, `card-grid`, `form-grid`,
   `state-panel`, etc.), sempre reutilizando os tokens e variaveis ja definidos.
4. Ajustar a view do painel para consumir o shell consolidado sem breadcrumb
   duplicado.
5. Validar por testes web e build frontend.

## Escopo desta fatia

- Shell autenticado compartilhado.
- Compatibilidade visual das views web ja existentes.
- Nao inclui reescrita integral de cada modulo (`escolas`, `turmas`, `provas`,
  `perfil`, `configuracoes`, etc.), que continuam para as proximas fatias.

## Resultado esperado

Ao final desta etapa, o login, o painel e as demais telas autenticadas passam a
compartilhar a mesma linguagem gov.br, com navegacao consistente e base pronta
para a reconstrucao tela a tela das proximas fases.
