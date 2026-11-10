# 073 — Layout responsivo — Planejamento

**Implementação:** Concluída
**Etapa:** Concluída
**Conforme princípios:** versão 1
**Prioridade no plano mestre:** sem posição na fila (concluída; fora das ondas do §5)
**Natureza:** existente documentada a partir do código
**Origem:** tronco original — auditoria de layout de 30/10/2026 (docs/23 (`docs/23-plano-layout-responsivo.md`), etapas L0–L8)
**Atualizado em:** 2026-11-09, por lucasgenial

## 1. Objetivo
Dar a todas as telas e perfis um padrão único de layout que funcione do celular (375 px) ao 4K (3840 px): sem rolagem lateral, sem menu quebrando, com componentes compartilhados e sem dados fixos ou falsos. A régua serviu também de referência visual para os apps móveis futuros.

## 2. Escopo
- **Inclui:**
  - L0: correções urgentes (menu quebrando pelo selo da escola, rolagem lateral do gabarito no celular, conteúdo cortado, idioma pt-BR, remoção de textos fixos/falsos);
  - L1: fundação com régua de larguras, conteúdo na tela toda, escala por faixa de largura, tokens, componentes Blade base e a página interna `/referencia-visual`;
  - L2: menu lateral no computador e barra inferior no celular;
  - L3 a L7: aplicação do padrão em painéis, listas e detalhes, formulários, correção e relatórios (impressão A4 preservada), páginas públicas e login;
  - L8: verificação contínua (`scripts/auditoria/rodar.sh`, `scripts/verificar_telas.py` na CI).
- **Não inclui:**
  - identidade visual (cores, fonte, marca): [FN-070](../070-identidade-visual/) e [FN-071](../071-marca-documentos/);
  - padrão de tabelas e cards: [FN-074](../074-tabelas-padrao/);
  - migração visual para Tailwind e a nova estrutura autenticada: [FN-075](../075-tailwind-ui/);
  - carregamento, estados de erro e breadcrumb: [FN-076](../076-carregamento-estados/).

## 3. Fontes de verdade
- Mockups: `mockups/` (inventário em docs/01 (`docs/01-inventario-dos-mockups.md`)); style system em `style-system/`; tokens em `apps/web/public/css/app.css`.
- Documento de origem: docs/23 (`docs/23-plano-layout-responsivo.md`) (decisões 1 a 6 de 30/10/2026).
- Casos de uso: [casos-de-uso.md](casos-de-uso.md). Perfis: docs/03 (`docs/03-perfis-e-permissoes.md`) (todos os perfis usam o layout).
- Plano mestre: [§2 Qualidade transversal](../../planejamento/00-plano-mestre.md), §6 (linha do docs/23).

## 4. Dependências
| Depende de | Por quê | Situação |
|---|---|---|
| Nenhuma | Fundação visual inicial | — |

**Funcionalidades que dependem desta:** FN-070 (identidade visual, decisão 6 do docs/23), FN-074 (tabelas partiram dos componentes da L1/L4), FN-075 (TW2 substituiu a estrutura de navegação e a escala da área logada criadas em L1/L2), FN-072 (páginas públicas da L7).

## 5. Fases e subfases
| Subfase | Entrega | Critério de pronto | Status | Evidência |
|---|---|---|---|---|
| 1.1 (L0) | Correções urgentes | Nenhuma quebra de menu em 1024–3840; nenhuma rolagem lateral em 375; nenhum dado fixo | Entregue (produção) | PR #25 |
| 1.2 (L1) | Fundação: régua, conteúdo na tela toda, escala, tokens, componentes, `/referencia-visual` | Componentes documentados na página de referência | Entregue (produção) | PR #28 |
| 1.3 (L2) | Navegação lateral no computador, barra inferior no celular | Mesmo menu em todos os perfis e larguras | Entregue (produção) | PR #29 |
| 1.4 (L3) | Painéis por perfil | Painéis ocupam ≥ 80% da tela em 1920–3840 sem esticar texto | Entregue (produção) | PR #30 |
| 1.5 (L4) | Listas e detalhes | Sem `<style>` próprio nessas telas | Entregue (produção) | PR #31 |
| 1.6 (L5) | Formulários | Mesmo formulário em todas; uma coluna no celular | Entregue (produção) | PR #32 |
| 1.7 (L6) | Correção e relatórios | Quadros usam a largura; PDF igual ao anterior | Entregue (produção) | PR #33 |
| 1.8 (L7) | Páginas públicas e login | Layout correto em 375–3840 | Entregue (produção) | PR #34 |
| 1.9 (L8) | Verificação contínua | Script de auditoria e checagem na CI | Entregue (produção) | docs/23 (30/10/2026); `scripts/auditoria/rodar.sh`; `scripts/verificar_telas.py` no workflow da CI |

## 6. Contrato técnico
- Sem API nem banco: entrega exclusivamente de apresentação no `apps/web`.
- Componentes Blade compartilhados em `apps/web/resources/views/components/` (`pagina`, `cartao`, `indicador(es)`, `aviso`, `vazio`, `icone` e outros); layouts `app`, `auth`, `public` e `estado`.
- Escala por faixa de largura via `--escala` em `apps/web/public/css/app-conteudo-legado.css` (1,125 ≥ 1920 px; 1,25 ≥ 2560 px; 1,5 ≥ 3200 px). Na área logada, a estrutura TW2 de FN-075 fixa `--escala:1` e `zoom:1` (`.tw2-app` em `estrutura-app.css`); a escala continua só na área pública (`.area-publica` em `app.css`).
- Idioma: `APP_LOCALE=pt_BR` (`apps/web/.env.example`, `config/app.php`).
- Desempenho: não aplicável como entrega própria; medições seguem FN-077.

## 7. Interface
- Larguras de referência da auditoria: 375, 768, 1280, 1920, 2560 e 3840 px.
- Celular e tablet: barra inferior e menu "Mais". Computador: menu lateral (hoje na versão TW2, 256/80 px).
- Conferência visual registrada por texto, sem prints. A L8 gera as telas numa pasta temporária apagada ao fim e nunca as versiona.
- A evidência desta funcionalidade são os PRs e os critérios registrados em texto. Nenhuma captura de tela faz parte dela (regra "sem prints" de 09/11/2026; a CI bloqueia novas imagens com `scripts/verificar_imagens.py`).

## 8. Riscos e decisões pendentes do responsável
| Item | Opções | Recomendação | Decisão |
|---|---|---|---|
| Capturas de tela citadas no docs/23 §2 e §7 (plano mestre §6) | Remover a referência / manter | Referência removida nesta reescrita; o docs/23 permanece como histórico | Regra "sem prints" de 09/11/2026 (CLAUDE.md) |
| Escala por `zoom` (L1) substituída na área logada pela estrutura TW2 (FN-075) | — | Registrar que a decisão da L1 sobre escala vale hoje só para a área pública; revisões de 4K da área logada passam a ser responsabilidade de FN-075 (TW7) | Sem decisão pendente; registro |
| Auditoria L8 roda sobre cópia de desenvolvimento no CT 111 (`rodar.sh` usa `pct exec 111`) | Manter / apontar para a homologação | Avaliar em FN-003/FN-008 se a auditoria deve rodar na VM 120 | Pendente (fora desta FN) |
| `gov.css` citado no docs/23 §4.5 | — | Não existe mais em `public/css`; tratar o docs/23 como histórico | Registro |

Decisões do §4 do plano mestre que tocam esta funcionalidade: nenhuma.

## 9. Ramos desta entrega
Nenhum ramo aberto. A funcionalidade está concluída; evoluções visuais seguem em FN-075.

## 10. Histórico
| Data | Mudança | Por |
|---|---|---|
| 2026-11-09 | Documento criado a partir do inventário e do código | lucasgenial |
