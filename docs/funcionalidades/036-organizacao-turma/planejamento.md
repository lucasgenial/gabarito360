# 036 — Organização da turma (TU1) — Planejamento

**Estado no tronco:** Em homologação
**Prioridade no plano mestre:** sem onda própria; restante (conferência autenticada, aposentar chaves legadas) na fila de produção §7 ("Organização da turma e imagens", PR #203–#209) e na trilha de qualidade
**Natureza:** existente documentada a partir do código
**Origem:** tronco — docs/175, docs/176, docs/tu1-revisao-fluxos.md, docs/tu1-r3-dashboard-analitico.md (fila declarada no docs/165: "TU1 antes de retomar EI1")
**Atualizado em:** 2026-11-09, por agente de documentação

## 1. Objetivo
Organizar o detalhe da turma em seções claras (Visão geral, Alunos, Disciplinas e professores, Provas, Acesso) e oferecer as operações da turma — vínculo docente composto, transferência de aluno entre turmas da mesma escola, arquivamento reversível e painel analítico com PDF — com autorização no servidor e histórico preservado.

## 2. Escopo
- **Inclui:** abas da turma (TU1-A..E); retorno do cadastro à aba Alunos e edição em página completa (R1); disciplina + professor no mesmo formulário (R2); PDF do recorte (R4); transferência entre turmas da mesma escola (R5); painel analítico com 6 KPIs, 4 gráficos e filtros (TU1-R3-A..C); orientação de impressão (TU1-R3-D); arquivamento explícito e reversível de turma e aluno (TU1-R3-E).
- **Não inclui:**
  - Cadastro básico de turma e aluno → [FN-033](../033-estrutura-escolar/planejamento.md).
  - Catálogo de disciplinas e atribuições temporais → [FN-038](../038-disciplinas-curriculo/planejamento.md).
  - Imagens da turma → [FN-037](../037-imagens-turmas-educadores/planejamento.md).
  - Relatório PDF da turma com paridade ao painel (TU1-PDF/P8.1) → FN-050.
  - Painéis de turmas, cards de agenda e avisos → FN-053, FN-056, FN-057.
  - Intervalos de matrícula e contexto histórico (MT1) → FN-026.

## 3. Fontes de verdade
- Mockups: `mockups/turmas.html`, `turma-detalhe-2.html` ([docs/01](../../01-inventario-dos-mockups.md) §13–§14); direção de design em `docs/operacao-20261102/tu1-direcao-design.md` (citada no docs/175).
- Documentos de origem: [docs/175](../../175-tu1-organizacao-da-turma.md), [docs/176](../../176-reconciliacao-solicitacoes.md), [tu1-revisao-fluxos](../../tu1-revisao-fluxos.md), [tu1-r3-dashboard-analitico](../../tu1-r3-dashboard-analitico.md).
- Casos de uso: [casos-de-uso.md](casos-de-uso.md).
- Perfis e permissões: [docs/03](../../03-perfis-e-permissoes.md).
- Plano mestre: [§2 FN-036](../../planejamento/00-plano-mestre.md).

## 4. Dependências
| Depende de | Por quê | Situação |
|---|---|---|
| FN-038 disciplinas-curriculo (DC1) | Aba Disciplinas e professores; vínculo docente composto | Em homologação |
| FN-014 perfis-institucionais (PF1) | Perfis que operam a turma (secretaria, vice) | Concluída |
| FN-018 seguranca-urls-sessao (UR4) | Referências fora das URLs, descritor BFF | Em homologação |
| FN-033 estrutura-escolar | Cadastro de turma e aluno | Parcial |

**Funcionalidades que dependem desta:** FN-050 (TU1-PDF parte das bases do painel), FN-053 (painéis de turmas), FN-037 (imagem no detalhe e no editar turma), FN-026 (MT1 parte da movimentação registrada).

## 5. Fases e subfases
| Subfase | Entrega | Critério de pronto | Status | Evidência |
|---|---|---|---|---|
| 1.1 (TU1-A..E) | Abas e operações independentes, publicação | Abas acessíveis; operações preservadas | Entregue (homologação) | PR #203, #204, #207 |
| 1.2 (R1) | Retorno do cadastro à aba Alunos; edição da turma em página completa | Retorno correto por perfil | Entregue (homologação) | PR #203, #204 |
| 1.3 (R2) | Disciplina + professor no mesmo formulário (`vinculos-docentes`, idempotente) | Transação sem concessão parcial | Entregue (homologação) | PR #204 |
| 1.4 (R3) | Dashboard da turma em modal | — | Substituída por TU1-R3 | PR #207 |
| 1.5 (R4) | PDF do recorte, retrato/paisagem | Mesmo recorte do painel | Entregue (homologação) | PR #204–#206 |
| 1.6 (R5) | Transferência entre turmas da mesma escola | Histórico, notas e conflitos preservados | Entregue (homologação) | PR #204–#206 |
| 1.7 (TU1-R3-A..F) | KPIs, gráficos, orientação de impressão, arquivamento | Revisões e CI | Entregue (homologação) | PR #207 |
| 1.8 | Conferência autenticada / aceite visual final | Registro textual da conferência | Pendente | docs/176 |
| 1.9 | Aposentar chaves legadas `*.desativar` e rotas toggle | Arquivamento com chave própria; toggles removidos | Pendente | tu1-r3 |

## 6. Contrato técnico (proporcional ao risco)
- **API:** `GET /v1/turmas/{id}` (detalhe com alunos e provas); `GET /v1/turmas/{id}/painel` e `/painel/pdf` (`relatorio.turma`; filtros `disciplina_id`, `professor_id` = professor autor, `inicio`, `fim`; PDF com `orientacao`); `GET /v1/turmas/{id}/vinculo-docente/opcoes` e `POST /v1/turmas/{id}/vinculos-docentes` (`turma.ver` + `disciplina.gerir` + `turma.vincular_professor`; `chave_idempotencia` UUID); `POST /v1/alunos/{id}/transferencias-turma` (`aluno.gerir` e perfil Coordenação/Direção/Vice-direção/Secretaria); `PUT /v1/turmas/{id}/arquivamento` e `PUT /v1/alunos/{id}/arquivamento` (`turma.desativar`/`aluno.desativar`; `chave`, `versao`, `esperado_ativo`); toggles legados `POST /v1/turmas/{id}/toggle`, `POST /v1/alunos/{id}/toggle`.
- **Modelo:** migrations aditivas `aluno_movimentacoes` e `vinculos_docentes_compostos` (eventos imutáveis, motivo cifrado); colunas opcionais em `turmas` (sala, capacidade, etapa, observações).
- **Concorrência e idempotência:** recibo imutável no vínculo composto (chave reutilizada com outro par → 409); transferência com `turma_origem_id` esperada e chave; recusa enquanto houver leitura OMR pendente/processando no espaço; arquivamento com versão e lock.
- **Desempenho:** agregados do painel no banco; PDF com arquivo temporário privado, revalidação do DTO após a geração (409 se os dados mudaram).

## 7. Interface
- Web: `turmas/show` com abas (`_abas`, `_alunos`, `_provas`, `_acesso`, `_painel`, `_painel_alunos`, `_painel_documento`), `turmas/edit`, `turmas/secao`, `alunos/{id}/transferir`, modal de transferência e de arquivamento.
- Abas como melhoria progressiva (tablist, setas, Home/End); só o fragmento de seção vai ao endereço.
- Pendente: conferência autenticada e aceite visual final, registrados por texto (sem prints).

## 8. Riscos e decisões pendentes do responsável
| Item | Opções | Recomendação | Decisão |
|---|---|---|---|
| §4 item 2 — gate de produção e fila acumulada (PR #203–#209 só na homologação) | Promoção em blocos / aguardar | Começar pela segurança; este bloco em seguida | Pendente |
| Arquivamento ainda sob as chaves legadas `turma.desativar`/`aluno.desativar` e toggles ativos | Criar chaves próprias e remover toggles / manter | Subfase 1.9 | Pendente |
| Inventários G5 × G6 divergiam (aceite visual final incerto × conferência autenticada pendente) | — | Plano mestre §6: Pendente | Registrado |
| Refino da diagramação do PDF para o quadro "Atenção/Top5" | — | Tratado em FN-050 | Registrado |

## 9. Ramos desta entrega
Nenhum aberto. TU-IMG nasceu como ramo do TU1-R3 e foi registrado como FN-037.

## 10. Histórico
| Data | Mudança | Por |
|---|---|---|
| 2026-11-09 | Documento criado a partir do inventário e do código | agente de documentação |
