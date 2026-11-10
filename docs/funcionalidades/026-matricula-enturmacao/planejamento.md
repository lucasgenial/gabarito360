# 026 — Matrícula e enturmação (MT1) — Planejamento

**Estado no tronco:** Parcial
**Prioridade no plano mestre:** Onda 1 — Módulos transversais e fundações de dados, ordem 5 (destrava P1.4.6, P4.4, TE1 e T6)
**Natureza:** evolução de existente (parte existente documentada a partir do código)
**Origem:** docs/115 §7 e §12 (MT1); docs/13 MP-035b (status de matrícula); docs/28 A5 (troca de turma com histórico)
**Atualizado em:** 2026-11-09, por lucasgenial

## 1. Objetivo
Registrar a vida escolar do aluno no tempo: matrícula por pessoa, escola e período, e enturmações com início e fim. Mudar de turma não move notas antigas; relatórios, PDFs e OMR usam o contexto histórico da aplicação.

## 2. Escopo
- **Inclui:**
  - Existente: movimentação entre turmas da mesma escola com motivo, chave idempotente e congelamento da turma de aplicação dos cartões (`aluno_movimentacoes`); arquivamento e desarquivamento de aluno e turma com versão e histórico (`cadastro_arquivamentos`), substituindo o status de três estados do MP-035b.
  - Pendente: MT1-A matrícula escolar; MT1-B enturmações temporais (início inclusivo/fim exclusivo); MT1-C relatórios, PDF e OMR pelo contexto histórico.
- **Não inclui:**
  - Aluno entre escolas → FN-025 (TE1).
  - Identidade de pessoa → FN-016 (a matrícula referencia pessoa quando conciliada).
  - Períodos letivos → FN-039 (consumidos pela matrícula; não confundir com enturmação).
  - Elegibilidade por aplicação nos painéis (P1.4.6, P3.3.5, P4.4) → FN-053, que aguarda esta funcionalidade.
  - Importação de alunos → FN-034.
  - Aposentar as rotas `toggle` de aluno/turma → FN-036.

## 3. Fontes de verdade
- Documentos de origem: [docs/115 §7, §11, §13](../../115-replanejamento-provas-identidade-transferencias.md); adendo de 07/11 ligando MT1 ao [docs/177](../../177-plano-paineis-turmas.md); [docs/13](../../13-roadmap.md) MP-035b; [docs/28](../../28-plano-mestre-proximos-modulos.md) A5; [tu1-revisao-fluxos](../../tu1-revisao-fluxos.md).
- Perfis e permissões: [docs/03](../../03-perfis-e-permissoes.md) (`aluno.gerir`, `aluno.desativar`, `turma.desativar`).
- Casos de uso: [casos-de-uso.md](casos-de-uso.md). Sem UC no docs/07.
- Mockups: turma/alunos do inventário (docs/01); tela "Transferir aluno entre turmas" sem mockup próprio (revisão TU1).

## 4. Dependências
| Depende de | Por quê | Situação |
|---|---|---|
| FN-033 estrutura-escolar | Turmas e alunos | Parcial |
| FN-016 identidade-pessoal | Matrícula referencia pessoa conciliada (pessoa+escola+período) | Parcial |
| FN-039 periodos-letivos | Período da matrícula; o plano mestre ordena 039 antes de 026 na Onda 1 | Candidata |
| FN-036 organizacao-turma | Telas da turma onde a movimentação é feita | Em homologação |

**Funcionalidades que dependem desta:** FN-025 (TE1), FN-053 (P1.4.6, P3.3, P3.4), FN-028 (T6), FN-034 (importação), FN-064 (L4-D/E).

## 5. Fases e subfases
| Subfase | Entrega | Critério de pronto | Status | Evidência |
|---|---|---|---|---|
| 1.1 Movimentação entre turmas da mesma escola | `MovimentarAlunoTurma`, `aluno_movimentacoes`, tela "Transferir aluno entre turmas" | Notas antigas preservadas na turma de aplicação | Entregue (homologação) | PR #204; migration `2026_11_06_000001_registrar_movimentacoes_turma` |
| 1.2 Arquivamento de aluno e turma | `ArquivamentoCadastro`, `cadastro_arquivamentos`, `PUT .../arquivamento` | Versão, chave idempotente e histórico; nenhum dado histórico encerrado | Entregue (homologação) | PR #207; migration `2026_11_06_000004_registrar_arquivamento_cadastros` |
| 2.1 (MT1-A) Matrícula | Matrícula pessoa+escola+período | Migration aditiva e backfill com manifesto | Pendente | docs/115 |
| 2.2 (MT1-B) Intervalos | Enturmações com início/fim; mudança atômica no instante de corte | Sem sobreposição indevida; modalidades paralelas admitidas | Pendente | docs/115 |
| 2.3 (MT1-C) Relatórios pelo contexto histórico | Relatórios, PDF e OMR usam turma da aplicação; turma nova distingue resultados anteriores | Cenário de aceite do docs/115 §13 | Pendente | docs/115 |

## 6. Contrato técnico
- **Existente:**
  - `POST /api/v1/alunos/{id}/transferencias-turma` (`permissao:aluno.gerir`; `MovimentarAlunoTurma::autorizado` exige perfil coordenador, dir_escolar, vice_dir_escolar ou secretario_escolar). Corpo: `turma_id`, `turma_origem_id`, `chave` (UUID), `motivo` (5–500). Mesma escola e mesmo espaço; bloqueia se houver leituras pendentes no espaço; fixa `cartoes.turma_aplicacao_id`; motivo cifrado.
  - `PUT /api/v1/alunos/{id}` com `turma_id` também move o aluno, sem motivo informado (registra "Alteração de turma pelo cadastro").
  - `PUT /api/v1/alunos/{id}/arquivamento` e `PUT /api/v1/turmas/{id}/arquivamento` (`aluno.desativar`/`turma.desativar`). Corpo fechado: `arquivado`, `esperado_ativo`, `versao`, `chave`, `motivo`. `READ COMMITTED` com até 3 tentativas no MariaDB.
- **Proposto (MT1):** `matriculas` e `enturmacoes` (ou equivalentes) com FK para pessoa quando conciliada; períodos início inclusivo/fim exclusivo; `pessoa_id`/`matricula_id`/`enturmacao_id` acrescentados gradualmente; legado sem contexto marcado como tal, sem reconstrução.
- **Desempenho:** relatórios pelo contexto histórico precisam de medição com 1000 usuários simultâneos (FN-077) antes da homologação.

## 7. Interface
Existente: "Transferir aluno entre turmas" (página e modal na turma), com revisão antes de confirmar e aviso de que o histórico permanece na turma da avaliação; arquivar/desarquivar na turma e no aluno. MT1: linha do tempo do aluno e indicação de turma atual × turma da avaliação nos relatórios; exigir revisão UI/UX.

## 8. Riscos e decisões pendentes do responsável
| Item | Opções | Recomendação | Decisão |
|---|---|---|---|
| §4 item 1 — aprovação do plano e das ondas (MT1 na Onda 1) | — | Aprovar | Pendente |
| §4 item 7 — priorizar FN-039 junto com FN-056 (períodos para recorte) | — | A do plano mestre | Pendente |
| ⚠ Rótulo "Transferir aluno"/rota `transferencias-turma` para mudança de turma, enquanto o docs/115 §2 chama o fluxo de "Movimentar matrícula" e reserva "Transferências" para FN-025 | Manter / renomear para "Mudar de turma" | Avaliar na revisão UI/UX de MT1 | Pendente |
| Dois caminhos movem o aluno (endpoint dedicado e `PUT alunos/{id}` com `turma_id`); o segundo não exige motivo nem chave | Unificar no MT1-B | Unificar | Pendente |
| No espaço pessoal a mudança de turma não grava `aluno_movimentacoes` | Registrar também / manter | Decidir no MT1-B | Pendente |
| Perfil `auxiliar_secretaria` tem `aluno.gerir` mas não movimenta (lista fixa de perfis) | Conferir com a matriz PF1 | Confirmar intenção | Pendente |

## 9. Ramos desta entrega
Nenhum ativo.

## 10. Histórico
| Data | Mudança | Por |
|---|---|---|
| 2026-11-09 | Documento criado a partir do inventário e do código | lucasgenial |
