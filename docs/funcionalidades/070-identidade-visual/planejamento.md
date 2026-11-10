# 070 — Identidade visual e gráficos — Planejamento

**Implementação:** Concluída
**Etapa:** Concluída
**Conforme princípios:** versão 1
**Prioridade no plano mestre:** sem onda (entregue em produção); pendência no §3: "Registrar decisões 1–2 do docs/26"
**Natureza:** existente documentada a partir do código
**Origem:** tronco original — docs/26 (V1–V5), docs/27 (G1–G3, detalhamento de V5)
**Atualizado em:** 2026-11-09, por lucasgenial

## 1. Objetivo
Dar ao Gabarito360 uma identidade própria (direção "Atual refinada": azul próprio, verde-água para atenção, fonte Figtree) e gráficos consistentes com ECharts, em claro e escuro e na impressão A4. Atende todos os usuários e visitantes.

## 2. Escopo
- **Inclui (entregue):** V1 base visual (cores, Figtree, escalas, menu lateral menos dominante); V2 página inicial e páginas públicas; V3 painéis e listas; V4 ícones e textos; V5/G1 base ECharts e componente `<x-grafico>`; G2 relatórios; G3 painéis e barra de progresso na correção.
- **Não inclui:** marca oficial (símbolo, wordmark, favicons) e cabeçalhos de documentos (FN-071); apresentação pública de pré-lançamento (FN-072); layout responsivo (FN-073); migração para Tailwind (FN-075); runtime de gráficos otimizado no F3 (FN-077).

## 3. Fontes de verdade
docs/26 (`docs/26-plano-identidade-visual.md`), docs/27 (`docs/27-plano-graficos-echarts.md`), style system (`style-system/`), tokens em `apps/web/public/css/app.css` (`--accent: #1d4f9e`, `--atencao: #0b7f75`). Código: `apps/web/resources/views/components/grafico.blade.php`, `layouts/_echarts.blade.php`, `apps/web/public/js/libs/echarts-6.1.0.common.min.js` e `echarts-g360.min.js`. Casos de uso: [casos-de-uso.md](casos-de-uso.md).

## 4. Dependências
| Depende de | Por quê | Situação |
|---|---|---|
| — | Fundação visual | — |

**Funcionalidades que dependem desta:** FN-071 (marca aplica-se sobre a identidade), FN-072 (páginas públicas), FN-053 e FN-049 (gráficos dos painéis e relatórios), FN-075 (Tailwind preserva os tokens).

## 5. Fases e subfases
| Subfase | Entrega | Critério de pronto | Status | Evidência |
|---|---|---|---|---|
| 1.1 (V1) | Base visual, Figtree, menu | Auditoria em 6 larguras | Entregue (produção) | PR #43 (`81104b4`) |
| 1.2 (V2) | Página inicial e páginas públicas | Idem | Entregue (produção) | PR #44 (`64964e3`) |
| 1.3 (V3) | Painéis e listas | Idem | Entregue (produção) | PR #45 (`bb70ddf`) |
| 1.4 (V4) | Ícones e textos | Idem | Entregue (produção) | PR #46 (`2b98558`) |
| 1.5 (V5/G1) | ECharts local e `<x-grafico>` com tabela equivalente | Carrega só em telas com gráfico; SVG | Entregue (produção) | PR #47 (`1ce92ef`) |
| 1.6 (G2) | Relatórios com ECharts; rosca trocada por meta | Impressão A4 conferida | Entregue (produção) | PR #48 (`f33f9c9`) |
| 1.7 (G3) | Painéis e barra de progresso | Idem | Entregue (produção) | PR #49 (`839c319`) |
| 1.8 | Registrar decisões 1 e 2 do docs/26 | Decisões registradas | Pendente (registro) | ver §8 |

## 6. Contrato técnico (proporcional ao risco)
ECharts versão fixa, servido localmente, sem CDN; renderização SVG; cada gráfico mantém tabela com os mesmos números para leitura sem cor e leitores de tela (docs/27). Sem API nova.

## 7. Interface
Temas claro e escuro pelos tokens; auditoria em 6 larguras antes de cada PR (docs/26). Conferências futuras registradas por texto, sem prints.

## 8. Riscos e decisões pendentes do responsável
| Item | Opções | Recomendação | Decisão |
|---|---|---|---|
| Decisão 1 do docs/26 (botão principal "Falar com a gente", sem oferta de 14 dias) | Registrar como aplicada | O código já usa "Falar com a gente" (`home/index.blade.php`); registrar formalmente | Incerto no plano mestre; aplicada no código |
| Decisão 2 do docs/26 (telas reais com dados fictícios, sem banco de imagens) | Registrar como aplicada | A home usa demonstrações fictícias (`home/_demonstracoes.blade.php`); registrar formalmente | Incerto no plano mestre; aparentemente aplicada |
| Evidência do plano mestre | Plano mestre cita "PR #45–#49"; V1 e V2 foram PR #43 e #44 | Corrigir a evidência no plano mestre (não alterado aqui) | — |

## 9. Ramos desta entrega
Nenhum.

## 10. Histórico
| Data | Mudança | Por |
|---|---|---|
| 2026-11-09 | Documento criado a partir do inventário e do código | lucasgenial |
