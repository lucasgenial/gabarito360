# 075 — Migração para Tailwind e UX transversal — Planejamento

**Implementação:** Parcial
**Etapa:** Em planejamento
**Prioridade no plano mestre:** trilha contínua de qualidade (§5): TW3 restante a TW6 aplicados dentro das entregas, nas telas que cada entrega tocar; janela própria para TW4–TW6 proposta só depois da Onda 2 (proposta, não aprovada)
**Natureza:** existente documentada a partir do código (evolução parcial)
**Origem:** pedido transversal durante P3.2 ("ações SMALL", [docs/184](../../184-acoes-small-tabelas.md)), substituído pela decisão do responsável de adotar Tailwind ([docs/185](../../185-migracao-tailwind.md)) em 08/11/2026; UX-D e UX-T recuperados na reconciliação [docs/176](../../176-reconciliacao-solicitacoes.md)
**Atualizado em:** 2026-11-09, por lucasgenial

## 1. Objetivo
Adotar o TailwindCSS como base visual de todas as telas, preservando a paleta do Gabarito360, as funções, os dados e os requisitos de uso, e eliminar o CSS espalhado por tela. Inclui padronizar ações, tabelas, formulários e diálogos (sem confirmações nativas do navegador) para todos os perfis.

## 2. Escopo
- **Inclui:**
  - TW1: fundação e prévia isolada de componentes (`/previas/tailwind/`, dados fictícios);
  - TW2: estrutura autenticada (cabeçalho, lateral 256/80 px, menu da conta pelo avatar, folha móvel, filtros de Turmas em modal, rodapé com versão);
  - TW3: ações, tabelas e cards de todas as famílias e dos renderizadores JS;
  - TW4: formulários e diálogos, sem `confirm`/`alert`/`prompt` nativos (absorve UX-D);
  - TW5: painéis e ferramentas (GridStack, ECharts, agenda, recorte de imagem, leitores);
  - TW6: autenticação, site público, páginas de erro, e-mails e documentos (PDF e impressão com escopo próprio);
  - TW7: validação transversal contínua;
  - versão do produto visível (0.1.0) em `apps/web/config/versao.php`.
- **Não inclui:**
  - componente `x-tabela` e regras de cards: [FN-074](../074-tabelas-padrao/) (TW3 migra o visual sobre ele);
  - carregamento regional, estados de erro e breadcrumb: [FN-076](../076-carregamento-estados/);
  - paleta e marca: [FN-070](../070-identidade-visual/), [FN-071](../071-marca-documentos/);
  - agenda como módulo: [FN-056](../056-agenda-calendario/) (TW5 só migra o visual).

## 3. Fontes de verdade
- Style system (`style-system/`) e tokens em `apps/web/public/css/app.css` (paleta preservada).
- Documentos de origem: [docs/184](../../184-acoes-small-tabelas.md), [docs/185](../../185-migracao-tailwind.md), [docs/186](../../186-previa-componentes-tailwind.md), [docs/187](../../187-tw2-conferencia-homologacao.md), [docs/188](../../188-tw3-acoes-direita.md), [docs/176](../../176-reconciliacao-solicitacoes.md).
- Regras permanentes em `AGENTS.md` (confirmação por modal da aplicação; Editar e Arquivar turma com ícone e texto).
- Casos de uso: [casos-de-uso.md](casos-de-uso.md). Perfis: [docs/03](../../03-perfis-e-permissoes.md).

## 4. Dependências
| Depende de | Por quê | Situação |
|---|---|---|
| FN-073 layout-responsivo | Base de componentes e larguras | Concluída |
| FN-074 tabelas-padrao | TW3 converte as famílias sobre o `x-tabela` | Em homologação |
| FN-070 / FN-071 | Paleta e marca preservadas | Concluída / Em homologação |

**Funcionalidades que dependem desta:** todas as entregas de interface das ondas, que pela proposta do §5 migram para Tailwind as telas que tocarem; FN-053 e FN-054 (TW5 nos painéis e no editor); FN-072 (TW6 no site público).

## 5. Fases e subfases
| Subfase | Entrega | Critério de pronto | Status | Evidência |
|---|---|---|---|---|
| 0.1 (ações SMALL, docs/184) | Compactar ações no CSS próprio | — | Substituída por TW | docs/185 |
| 1.1 (TW1) | Fundação, prévia isolada, FormValidation 2.4.0, Material Symbols | Aprovação humana da prévia (rodada 4) | Entregue (homologação) | PR #222 |
| 2.1 (TW2) | Estrutura autenticada | Testes de permissão e contexto; revisão independente; CI | Entregue (homologação) | PR #223 |
| 2.2 (TW2 complemento) | Rodapé "Gabarito360 - v0.1.0 - Homologação", rótulo "Nome ou série" | CI e publicação na homologação | Entregue (homologação) | PR #224; docs/187 |
| 2.3 (TW2 encerramento) | Smoke desktop e celular da release final | Registro por texto do smoke | Incerto | docs/187 ("Após smoke"); sem registro posterior |
| 3.1 (TW3 parcial) | Ações na última coluna, centralizadas; ordenação nos cabeçalhos com `aria-sort` | Inventário e HTML renderizado; teclado | Entregue (homologação) | PR #225; docs/188 |
| 3.2 (TW3 restante, absorve UX-T) | Todas as famílias de ações, tabelas, cards e renderizadores JS; retirar CSS concorrente | Matriz por família/template | Pendente | docs/188 |
| 4.1 (TW4, absorve UX-D) | Formulários e diálogos sem confirmação nativa | Nenhum `confirm`/`alert`/`prompt` nativo; CSRF, intenção e falha preservados | Pendente | docs/185 |
| 5.1 (TW5) | Painéis e ferramentas | Preferências GridStack e KPIs fixos preservados | Pendente | docs/185 |
| 6.1 (TW6) | Autenticação, site público, erros, e-mails, documentos | PDF/print com comparação própria | Pendente | docs/185 |
| 7.1 (TW7) | Validação transversal por perfil, largura, tema, zoom, teclado | Contínua por etapa | Contínua | — |

## 6. Contrato técnico
- Tailwind 4 compilado localmente (`@tailwindcss/vite`, `tailwindcss` ^4 em `apps/web/package.json`; `scripts/tailwind/build.mjs` e `build-estrutura.mjs`). Sem CDN em homologação ou produção.
- Fronteira atual: a estrutura TW2 está em `public/css/estrutura-app.css` (camadas Tailwind sem Preflight geral); o conteúdo ainda usa `app-conteudo-legado.css`, `tabelas.css` e folhas por tela. Botões, cards e campos no padrão Tailwind só valem hoje nas telas `.tela-turmas` e `.tela-turma`.
- A migração não altera API, rotas, CSRF, sessão, permissões nem banco. Hooks de JS por `data-*`.
- Desempenho: sem custo de runtime próprio; folhas versionadas por conteúdo (`@ativo`, FN-077/F3).
- Testes: `apps/web/tests/Feature/EstruturaTailwindTest.php`; auditoria `scripts/auditoria/previa-componentes.mjs`, `estrutura-app.mjs`.

## 7. Interface
- Lateral 256 px expandida / 80 px recolhida em ≥ 768 px; barra inferior e folha "Mais" abaixo disso; alvos de 44 px em toque.
- Ações SMALL: `min-h-8`, `px-3`, `gap-2`, `rounded-md`, `text-sm`; altura cresce com rótulo longo.
- Confirmações de negócio por modal da aplicação.
- Conferência registrada por texto (tema claro e escuro, larguras, zoom), sem prints.

## 8. Riscos e decisões pendentes do responsável
| Item | Opções | Recomendação | Decisão |
|---|---|---|---|
| Usuário coordenador com dois vínculos (Coordenador + Professor) na homologação, pedido no docs/186 | Criar / dispensar | Confirmar se existe; sem ele, a troca de vínculo do menu da conta não é conferida | Incerto |
| TW2 encerramento (smoke) | Registrar / refazer | Registrar o smoke por texto na próxima conferência da homologação | Incerto |
| UX-D: o inventário registra 1 `confirm(` restante; o código tem 1 `confirm(` (`correcao-acompanhamento.js`) e 4 `alert(` (`correcao-acompanhamento.js`, `correcao-camera.js`, `correcao-revisao.js`) | — | Tratar todos em TW4 | Registro de divergência inventário × código |
| Janela própria para TW4–TW6 | Dentro das entregas / janela dedicada | Proposta do §5: só depois da Onda 2 | Pendente (§4 item 1) |
| Produção | — | Bloco "Tailwind" do §7 | Pendente (§4 item 2) |

Decisões do §4 que tocam esta funcionalidade: item 1 (aprovação da priorização) e item 2 (gate de produção).

## 9. Ramos desta entrega
- Organização dos painéis da turma (mover Resultados por período e Agenda; reordenar grupos), pedida durante TW3: não pertence a esta FN; segue em FN-053/FN-054.

## 10. Histórico
| Data | Mudança | Por |
|---|---|---|
| 2026-11-09 | Documento criado a partir do inventário e do código | lucasgenial |
