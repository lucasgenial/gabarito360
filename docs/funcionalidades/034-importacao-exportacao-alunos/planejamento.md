# 034 — Importação e exportação de alunos — Planejamento

**Estado no tronco:** Pendente
**Prioridade no plano mestre:** Onda 3 (PROPOSTA, §5) — depende de FN-026 e de e-mail (FN-060)
**Natureza:** evolução de existente (só há o comando de terminal `g360:importar-alunos`)
**Origem:** tronco original — docs/13 MP-016 (importação por planilha), docs/28 A5, docs/70, docs/134
**Atualizado em:** 2026-11-09, por lucasgenial

## 1. Objetivo
Permitir que a secretaria e a coordenação carreguem e atualizem alunos em lote por planilha, com prévia e erros por linha, e exportem a lista de alunos do próprio escopo, sem depender de operação de terminal.

## 2. Escopo
- **Inclui:** serviço único de importação reaproveitando o comando atual; tela de importação com modelo de planilha documentado, prévia e erros por linha; atualização pela matrícula sem duplicar aluno; processamento pela fila com aviso ao concluir; exportação respeitando o escopo; modelos de e-mail da importação (E4 fases 2–4).
- **Não inclui:**
  - Mudança de turma com histórico (passos 8–10 do A5) → FN-026 (MT1) e FN-036 (transferência entre turmas, já entregue).
  - Dados dos responsáveis na planilha → FN-035 (A6 inclui os campos no CSV quando existir).
  - Notificação no sistema → FN-057.
  - Retenção dos arquivos CSV → FN-062 (§4 item 11).

## 3. Fontes de verdade
- Mockup: `mockups/turmas.html` (botão "Importar planilha"; [docs/01](../../01-inventario-dos-mockups.md) §13).
- Documentos de origem: [docs/28](../../28-plano-mestre-proximos-modulos.md) A5; [docs/13](../../13-roadmap.md) MP-016; [docs/70](../../70-a1-c-secretario-escolar.md); [docs/134](../../134-pf1-b-matriz-perfis-prioritarios.md); docs/04 RF-003.3 (formato "a definir").
- Casos de uso: [casos-de-uso.md](casos-de-uso.md).
- Perfis e permissões: [docs/03](../../03-perfis-e-permissoes.md) — `aluno.importar` (Administrador, Diretor Escolar, Coordenador padrão; Professor extra; Secretário Escolar padrão; Vice-diretor extra).
- Plano mestre: [§2 FN-034](../../planejamento/00-plano-mestre.md).

## 4. Dependências
| Depende de | Por quê | Situação |
|---|---|---|
| FN-026 matricula-enturmacao | Intervalos de matrícula e histórico antes de atualizar em lote | Parcial |
| FN-060 email-transacional | Modelos de e-mail da importação (E4) | Parcial |
| FN-002 base-operacao | Importação pela fila (B4) | Concluída |
| FN-033 estrutura-escolar | Turmas e alunos existentes | Parcial |
| FN-062 lgpd-inventario-retencao | Prazo de retenção dos CSV | Parcial |

**Funcionalidades que dependem desta:** FN-060 (E4 fases 2–4 dependem de FN-034); FN-064/FN-062 (RL18 depende de T3/A5); FN-035 (campos de responsável no CSV).

## 5. Fases e subfases
| Subfase | Entrega | Critério de pronto | Status | Evidência |
|---|---|---|---|---|
| 0.1 | Comando `g360:importar-alunos` (CSV CLASS;NAME;MATRICULA) | Simulação sem `--aplicar`; recusa transferência implícita | Entregue (código) | `apps/api/app/Console/Commands/ImportarAlunos.php`, commit 6598919 |
| 1.1 (A5-1, A5-3) | Serviço único e formato da planilha documentado | Formato aprovado; modelo publicado | Pendente | formato "a definir" |
| 1.2 (A5-2, A5-4, A5-5) | Rota e tela de importação com prévia e erros por linha; atualização por matrícula | Testes de importação com erros e repetição | Pendente | `aluno.importar` sem rota |
| 1.3 (A5-6) | Importação pela fila e aviso ao concluir | Aviso entregue; idempotência | Pendente | — |
| 1.4 (A5-2, A5-7) | Exportação respeitando o escopo | Exporta só o escopo do ator | Pendente | — |
| 1.5 (E4) | Modelos de e-mail da importação | Modelos aprovados | Pendente | — |

## 6. Contrato técnico (proporcional ao risco)
- **Existente (terminal):** `g360:importar-alunos {escola_id} {arquivo} --serie --turno --ano --usuario --vinculo --espaco --aplicar`. Exige do ator `aluno.importar`, `aluno.gerir` e `turma.gerir` (`ContextoImportacao`). Sem `--aplicar` (com espaços ativos), apenas simula. Trava ator, espaço e escola; cria turma ausente pelo nome e ano; atualiza aluno existente pela matrícula no mesmo espaço; recusa turma arquivada, aluno arquivado, matrícula de outra escola e transferência implícita.
- **Proposto:** endpoint de prévia e de confirmação com chave de idempotência; processamento em fila (job) com recibo; exportação CSV/XLSX paginada por escopo; formato a aprovar (XLSX citado no docs/70 e docs/134; CSV no docs/28).
- **Dados pessoais:** arquivo enviado é dado pessoal; armazenamento privado e expurgo conforme FN-062.
- **Desempenho:** importação grande fora da requisição; medição de carga conforme FN-077 (1000 usuários).

## 7. Interface
- Tela inexistente. Hoje há só o aviso "A importação por planilha ainda não está disponível." em `membros/perfis` para Secretário Escolar e Vice-diretor.
- Proposta: ação "Importar planilha" na lista de turmas/alunos (mockup `turmas.html`), passos enviar → prévia com erros por linha → confirmar → acompanhamento; estados de carga, vazio e erro conforme FN-076.

## 8. Riscos e decisões pendentes do responsável
| Item | Opções | Recomendação | Decisão |
|---|---|---|---|
| §4 item 1 — aprovação do plano mestre e da priorização | Aprovar / ajustar | Aprovar §1–§3 e Onda 0 | Pendente |
| §4 item 11 — LGPD operacional (prazos abertos, incluindo CSV) | Pacote único de retenção | Aprovar na Onda 3 | Pendente |
| Formato da planilha (CSV × XLSX; colunas) | CSV do comando atual / XLSX | Decidir antes de 1.1 | Pendente |
| ⚠ Divergência plano mestre × código: o plano diz "tela mostra 'Em breve'"; o código mostra o aviso "A importação por planilha ainda não está disponível." apenas em `membros/perfis` e não há botão de importação nas telas de turmas/alunos | Registrar | Ajustar o texto do plano mestre na próxima revisão | Pendente |

## 9. Ramos desta entrega
Nenhum aberto.

## 10. Histórico
| Data | Mudança | Por |
|---|---|---|
| 2026-11-09 | Documento criado a partir do inventário e do código | lucasgenial |
