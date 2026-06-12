# Fundamentos do Design System

## 1. Objetivo

O Design System do Gabarito360 estabelece uma linguagem visual única para web, mobile e dashboards. Ele deve transmitir confiança, transparência, modernidade, simplicidade e brasilidade, com aparência de serviço público moderno e acolhedor.

As telas e fluxos web em `style-system/` são a referência de composição. Elas
devem ser convertidas em componentes compartilhados e dados reais; CSS inline,
JavaScript duplicado e dados estáticos do protótipo não devem ser copiados para
produção.

## 2. Princípios visuais

- **Simplicidade:** cada tela deve deixar clara a próxima ação esperada.
- **Leveza:** evitar excesso de cores, sombras, divisórias e conteúdo simultâneo.
- **Transparência:** status, filtros, critérios e dados provisórios devem ser explícitos.
- **Acessibilidade:** atender WCAG 2.2 AA desde a criação do componente.
- **Responsividade:** adotar mobile first e adaptar conteúdo aos breakpoints oficiais.
- **Consistência:** reutilizar tokens e componentes entre módulos e plataformas.
- **Privacidade:** revelar somente os dados pessoais necessários ao fluxo autorizado.

## 3. Governança dos tokens

`docs/ui_token_gov_brasil.json` é a fonte implementável oficial. Nomes semânticos devem ser consumidos pelas aplicações; valores hexadecimais, pixels e sombras não devem ser repetidos diretamente em componentes.

Ordem de decisão:

1. usar token semântico existente;
2. usar token de marca ou fundação quando não existir equivalente semântico;
3. propor extensão documentada quando não houver token adequado;
4. nunca introduzir valor hardcoded sem justificativa documentada.

Quando `docs/SDGB.md` e o JSON apresentarem valores diferentes, prevalece o JSON. Por exemplo, o modo escuro deve usar `themes.dark.background.*` do JSON.

O tema inicial obrigatório é `light`. A aplicação somente usa `dark` após ação
explícita do usuário, com preferência persistida.

## 4. Identidade visual

### 4.1 Cores de marca

| Token | Valor | Uso recomendado |
|---|---:|---|
| `brand.colors.verdeAmazonia` | `#00D000` | sucesso, destaque institucional secundário |
| `brand.colors.amareloSol` | `#FFD000` | atenção e realce |
| `brand.colors.azulAtlantico` | `#183EFF` | ação primária e informação |
| `brand.colors.vermelhoUrucum` | `#FF0000` | erro e perigo |
| `brand.colors.cinzaHarpia` | `#3C3C3C` | neutralidade |
| `brand.colors.brancoPaz` | `#FFFFFF` | contraste e superfícies claras |

Cores de marca não substituem tokens semânticos. Cor nunca deve ser o único meio de transmitir estado.

### 4.2 Temas semânticos

Componentes devem consumir:

- `themes.light.background`, `text`, `border` e `action`;
- `themes.dark.background`, `text`, `border` e `action`;
- `semantic.success`, `info`, `warning`, `danger` e `neutral`.

## 5. Tipografia

- Família principal: `brand.typography.primary`, Rawline.
- Fallback oficial: `brand.typography.fallback`, Verdana, Arial e sans-serif.
- Hierarquia: `typography.h1`, `h2`, `h3`, `h4`, `body`, `bodySmall`, `caption` e `button`.
- Textos corridos usam `body`; metadados usam `bodySmall` ou `caption`.
- Títulos devem respeitar a ordem semântica da página, independentemente do tamanho visual.
- Não usar somente peso ou cor para indicar significado.

## 6. Espaçamento e layout

A escala oficial é `spacing.0`, `1`, `2`, `3`, `4`, `5`, `6`, `8`, `10`, `12` e `16`, variando de `0px` a `64px`.

Regras:

- usar a escala para margens, gaps e paddings;
- preservar agrupamento visual por proximidade;
- evitar valores intermediários não tokenizados;
- usar os breakpoints `xs`, `sm`, `md`, `lg`, `xl` e `2xl`;
- manter conteúdo principal legível e ações críticas acessíveis em telas pequenas.

## 7. Bordas, raios e sombras

- Raios disponíveis: `radius.none`, `sm`, `md`, `lg`, `xl` e `pill`.
- Bordas devem usar `themes.*.border.default`, `strong` ou `focus`.
- Sombras claras: `shadow.sm`, `md` e `lg`.
- Sombras escuras: `shadow.darkSm` e `darkMd`.
- Elevação deve indicar hierarquia funcional, não decoração.
- Cards, modais e componentes devem respeitar seus tokens específicos.

## 8. Movimento

- Usar `motion.durationFast`, `durationDefault`, `durationSlow` e `motion.easing`.
- Interações comuns não devem ultrapassar `200ms`.
- Respeitar preferência por movimento reduzido.
- Loading deve priorizar skeleton screens; evitar spinners longos.

## 9. Estados obrigatórios

Todo componente interativo deve considerar, quando aplicável:

| Estado | Requisito |
|---|---|
| Padrão | ação e propósito identificáveis |
| Hover | feedback sem deslocamento de layout |
| Focus visible | anel de foco tokenizado e perceptível |
| Active/pressed | confirmação visual imediata |
| Disabled | aparência e semântica de indisponibilidade |
| Loading | impedir repetição e informar progresso |
| Success | texto, ícone e cor semântica |
| Warning | orientação clara antes de continuar |
| Error | linguagem humana e ação de recuperação |
| Empty | ícone, mensagem e ação sugerida |

## 10. Catálogo obrigatório

O catálogo inicial inclui: Button, Input, Textarea, Select, DatePicker, Modal, Drawer, Card, Table, DataTable, Badge, Toast, Tooltip, Accordion, Tabs, Breadcrumb, Pagination, Avatar e Dashboard Card.

Cada plataforma deve mapear esse catálogo para componentes compartilhados e documentar exceções de interação específicas da web ou do mobile.
