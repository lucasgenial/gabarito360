# 076 — Carregamento, estados de erro e navegação — Planejamento

**Implementação:** Parcial
**Etapa:** Em planejamento
**Conforme princípios:** versão 1
**Prioridade no plano mestre:** trilha contínua de qualidade (§5): carregamento regional aplicado dentro das entregas, nas telas que cada entrega tocar
**Natureza:** existente documentada a partir do código (evolução parcial)
**Origem:** UI1 — interrupção pontual antes de TU1, pedida pelo responsável em 06/11/2026 (docs/174 (`docs/174-ui1-carregamento-e-estados-de-erro.md`)); breadcrumb, toast e carregamento progressivo nasceram na OMR2-C2 (docs/159 (`docs/159-omr2-resultado-e-historico-integrado.md`)); UI1-R registrado na reconciliação docs/176 (`docs/176-reconciliacao-solicitacoes.md`)
**Atualizado em:** 2026-11-09, por lucasgenial

## 1. Objetivo
Dar a todas as telas um comportamento previsível de carregamento, erro e orientação: casca do aplicativo sempre visível, esqueleto só na região pendente, erros estáticos com ações seguras, breadcrumb coerente com a origem e notificações (toast) padronizadas. Atende todos os perfis.

## 2. Escopo
- **Inclui:**
  - UI1-A: componente único de carregamento (`x-carregando`) na estrutura, na ponte de navegação e nos erros HTML; preloader integral só na aba que acabou de confirmar a troca de vínculo;
  - UI1-B: falha interrompe animação e `aria-busy`, mostra ações seguras, sem reenviar POST; 401/419 levam ao login; 409 da aba antiga preservado;
  - UI1-C: páginas de erro (`errors/estado.blade.php` para 401, 403, 404, 419, 429, 500, 503, 4xx, 5xx) sem detalhe interno; `Retry-After`; `noscript`; movimento reduzido;
  - UI1-D: conferência, revisão, CI e publicação na homologação;
  - motor de carregamento regional por promessa (`public/js/carregamento.js`, `window.G360Carga`);
  - breadcrumb central por rota nomeada e dados autorizados (`App\Support\Breadcrumbs`) e toast (`window.G360Toast`, Solid Toast 0.5.0 com SolidJS 1.9.18, distribuídos localmente);
  - UI1-R: carregamento por região em toda a aplicação e breadcrumb nas demais telas (pendente).
- **Não inclui:**
  - lista de notificações e central de avisos: [FN-057](../057-notificacoes/) (o toast é só o retorno imediato de uma ação);
  - abas da turma sob demanda e miniaturas por visibilidade (F4-A/F4-B): [FN-077](../077-desempenho/), que usam este motor;
  - estados por região dos painéis (P3.4): [FN-053](../053-paineis-turmas/);
  - visual Tailwind das páginas de erro: TW6 de [FN-075](../075-tailwind-ui/).

## 3. Fontes de verdade
- Documentos de origem: docs/174 (`docs/174-ui1-carregamento-e-estados-de-erro.md`), docs/159 (`docs/159-omr2-resultado-e-historico-integrado.md`) (OMR2-C2), docs/176 (`docs/176-reconciliacao-solicitacoes.md`) (UI1-R).
- ADR-195 item 8 (frontend progressivo: loader global não substitui tratamento regional) — docs/195 (`docs/195-adr-fundamentos-desempenho.md`).
- Casos de uso: [casos-de-uso.md](casos-de-uso.md); relacionados UC-004 e UC-005 de docs/07 (`docs/07-casos-de-uso.md`). Perfis: docs/03 (`docs/03-perfis-e-permissoes.md`).

## 4. Dependências
| Depende de | Por quê | Situação |
|---|---|---|
| FN-018 (UR1–UR4) | Ponte de navegação UR4 e revisão de contexto por aba | Em homologação |
| FN-047 (OMR2) | Breadcrumb, toast e carregamento progressivo nasceram na correção/resultado | Em homologação |

**Funcionalidades que dependem desta:** FN-077 (F4-A/F4-B usam o motor regional), FN-053 (P3.4 estados por região), FN-057 (o toast é o retorno visual das ações), todas as telas que migrarem para carregamento regional.

## 5. Fases e subfases
| Subfase | Entrega | Critério de pronto | Status | Evidência |
|---|---|---|---|---|
| 1.1 (UI1-A) | Componente único de carregamento; preloader só na troca de vínculo | Sem alterar seleção, sessão ou API | Entregue (homologação) | PR #201 |
| 1.2 (UI1-B) | Falha estática com ações seguras | Sem reenvio de POST; 401/419/409 preservados | Entregue (homologação) | PR #201 |
| 1.3 (UI1-C) | Páginas de erro, `Retry-After`, `noscript`, movimento reduzido | Nenhum detalhe interno de exceção | Entregue (homologação) | PR #201 |
| 1.4 (UI1-D) | Conferência, revisão, CI, publicação; include do modal da correção dentro de `content` | Homologação | Entregue (homologação) | PR #201, #202 |
| 2.1 (OMR2-C2) | Breadcrumb central e toast; carregamento progressivo na correção e no histórico do resultado | Revisão UI/UX; foco preservado em retry e toast | Entregue (homologação) em prova, correção e resultado | docs/159; FN-047 (PR #188–#190) |
| 3.1 (UI1-R) | Carregamento por região/card em toda a aplicação | Inventário das regiões monolíticas e migração | Parcial | docs/176; regiões existentes: correção, histórico do resultado, abas da turma (F4-B) |
| 3.2 (breadcrumb nas demais telas) | Mapear as demais rotas no `Breadcrumbs` | Trilha por rota nomeada em todas as telas logadas | Pendente | `app/Support/Breadcrumbs.php` cobre só `provas.index`, `provas.show`, `correcao.show`, `correcao.revisao`, `resultados.show` |

## 6. Contrato técnico
- Sem mudança de API: a apresentação de exceções HTTP pertence ao Web. Status, JSON e `Retry-After` da API são preservados.
- Motor regional: uma promessa por região; esqueleto na primeira carga; em atualização, mantém os dados anteriores e mostra "Atualizando…"; em erro, "Não foi possível carregar este bloco." ou "Não foi possível atualizar. Os dados anteriores foram mantidos." com botão "Tentar novamente".
- Breadcrumb: só rotas internas nomeadas, origem (`from`) validada contra permissões (`correcao.acompanhar`, `resultado.ver`, `turma.ver`); nunca acrescenta parâmetros a documentos assinados. Telas não mapeadas usam a seção `breadcrumb` da própria view.
- Toast: canto superior direito, em fluxo no início do conteúdo; sucesso 5 s, informação 8 s, erro e carregamento persistentes; máximo 3.
- Testes: `apps/web/tests/Feature/BreadcrumbsTest.php`, `EstadoTelaWebTest.php`.
- Desempenho: carregamento regional segue a ADR-195 e as medições de FN-077 (padrão de 1000 usuários simultâneos).

## 7. Interface
- Estados: carregando (`role=status`, `aria-busy`), vazio, erro estático com foco, sem flash nem giro; animação limitada a três ciclos; `prefers-reduced-motion` respeitado.
- Celular e desktop; tema claro e escuro.
- Conferência registrada por texto, sem prints. Estados de erro determinísticos são ensaiados localmente, sem forçar erro na homologação compartilhada.

## 8. Riscos e decisões pendentes do responsável
| Item | Opções | Recomendação | Decisão |
|---|---|---|---|
| Ritmo do UI1-R | Migração integral dedicada / dentro das entregas | Proposta do §5: cada entrega migra as telas que tocar | Pendente (§4 item 1) |
| Produção | — | Bloco "Carregamento e estados" do §7 | Pendente (§4 item 2) |

Decisões do §4 que tocam esta funcionalidade: item 1 (priorização) e item 2 (gate de produção).

## 9. Ramos desta entrega
Nenhum ramo aberto.

## 10. Histórico
| Data | Mudança | Por |
|---|---|---|
| 2026-11-09 | Documento criado a partir do inventário e do código | lucasgenial |
