# Tokens Web Congelados na R4

## 1. Estado

Este documento congela o contrato visual implementavel da aplicacao web a partir
da R4, concluida em 12 de junho de 2026.

A ordem de precedencia permanece:

1. `style-system/` define composicao, hierarquia e comportamento;
2. `docs/ui_token_gov_brasil.json` define valores visuais implementaveis;
3. `docs/SDGB.md` define principios e requisitos normativos.

O espelho web versionado esta em `backend/resources/css/tokens.css`. Componentes
consomem somente propriedades semanticas desse arquivo por meio de
`backend/resources/css/app.css`.

## 2. Reconciliacao

| Tema | Decisao congelada |
|---|---|
| Cores, tipografia, espacamentos, raios, sombras e movimento | usar os valores do JSON oficial |
| Composicao do shell | preservar sidebar, cabecalho, contexto, conta, breadcrumb e drawer identificados no mockup |
| Faixa governamental do mockup | nao implementar enquanto nao houver vinculacao institucional aprovada |
| Tema inicial | sempre `light`, inclusive quando o sistema operacional estiver em modo escuro |
| Tema escuro | aplicar somente apos escolha explicita persistida em `localStorage` sob a chave `g360-theme` |
| Graficos | usar `charts.series`, `gridLight/gridDark` e `textLight/textDark`, sempre com alternativa tabular |
| Texto sobre ações | usar aliases `--action-*-text` compostos apenas por cores oficiais e validados em WCAG AA |
| CSS inline e valores locais de tela | proibidos; excecoes estruturais devem ser registradas neste documento |

## 3. Extensoes Estruturais

As extensoes abaixo nao criam identidade visual nova. Elas resolvem dimensoes de
layout e camadas ausentes no JSON:

| Propriedade | Valor | Motivo |
|---|---:|---|
| `--layout-auth-max` | `480px` | manter formularios de acesso legiveis |
| `--layout-content-max` | `1280px` | limitar o conteudo ao breakpoint `xl` |
| `--layout-copy-max` | `768px` | limitar blocos de leitura ao breakpoint `md` |
| `--layout-filter-control-min` | `180px` | preservar controles de filtro antes da quebra |
| `--layout-min` | `360px` | viewport compacto minimo homologado |
| `--layout-table-min` | `720px` | ativar rolagem controlada somente dentro da tabela |
| `--layer-sticky` | `20` | manter o cabecalho acima do conteudo |
| `--layer-overlay` | `40` | manter menus, drawers e tooltips acima do shell |
| `--layer-skip-link` | `50` | manter o atalho de acessibilidade acima das camadas |

Os breakpoints CSS de `640px`, `768px` e `1024px` correspondem diretamente a
`breakpoints.sm`, `breakpoints.md` e `breakpoints.lg`. Custom properties nao
podem ser usadas em condicoes de media query.

## 4. Contrato de Implementacao

- `tokens.css` pode conter valores literais porque e o espelho auditavel da fonte
  oficial.
- aliases semanticos podem combinar tokens oficiais quando o par direto nao
  atingir contraste WCAG AA; o tema escuro usa preto oficial sobre o azul
  primario porque `text.inverse` atingiria somente `4,03:1`.
- `app.css`, componentes Blade e JavaScript nao devem repetir cores, sombras,
  raios ou dimensoes visuais literais.
- `html[data-theme="light"]` e o estado inicial dos layouts.
- `html[data-theme="dark"]` e o unico seletor que ativa o tema escuro.
- `prefers-color-scheme: dark` nao deve ativar tema automaticamente.
- `prefers-reduced-motion: reduce` permanece obrigatorio.
- novas extensoes devem ser documentadas antes de serem adicionadas.

## 5. Verificacao

```powershell
cd backend
php artisan test --filter=DesignSystemTest
npm.cmd run build
node --check resources/js/app.js
```
