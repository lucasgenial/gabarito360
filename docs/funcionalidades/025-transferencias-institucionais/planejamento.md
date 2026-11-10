# 025 — Transferências institucionais (TR1, TE1, UX1) — Planejamento

**Estado no tronco:** Pendente
**Prioridade no plano mestre:** Onda 3 — Ciclo de vida de dados pessoais e institucional (TR1 → TE1 → UX1)
**Natureza:** nova (substitui a política de FN-024)
**Origem:** docs/115 §7–§9 e §12 (replanejamento de 03/11/2026), docs/116, 117, 139
**Atualizado em:** 2026-11-09, por lucasgenial

## 1. Objetivo
Permitir a cessão e o recebimento controlados de dados e histórico de alunos entre escolas e espaços, com autoridade institucional explícita em cada lado, corte temporal, recibo e auditoria imutável. A origem preserva seus fatos; o destino recebe só o autorizado e produz fatos novos isolados.

## 2. Escopo
- **Inclui:**
  - **TR1 — controle institucional:** capacidades separadas para solicitar, confirmar, autorizar e executar; delegação por caso a Suporte/Desenvolvedor; auditoria append-only; notificações das etapas.
  - **TE1 — aluno entre escolas:** ingresso pelo destino (CPF ou identidade provisória verificada), solicitação de cessão com categorias e corte, decisão da origem, snapshot versionado cifrado no destino, recebimento e retificação por delta.
  - **UX1 — módulo Transferências:** tabela de solicitações com no máximo as ações Transferir e Receber; assistente por etapas; pendências e notificações.
- **Não inclui:**
  - Duplicar prova → FN-042.
  - Mudança de turma na mesma escola e matrícula temporal → FN-026 (MT1, pré-requisito).
  - Identidade de pessoa e conciliação → FN-016 (pré-requisito).
  - Convite de vínculo CPF-primeiro → FN-017.
  - Continuidade do professor independente ao sair da escola → FN-027 (PA1-D).
  - Encerramento de cliente → FN-028.
  - Exportação de dados do titular → FN-064.
  - Notificações como módulo → FN-057 (transversal; ver §8).

## 3. Fontes de verdade
- Documentos de origem: [docs/115 §7–§9, §11–§13](../../115-replanejamento-provas-identidade-transferencias.md), [docs/116](../../116-catalogo-nacional-instituicoes.md), [docs/117](../../117-r0-conciliacao-e-marcos-de-entrega.md), [docs/139](../../139-id1-dc1-ativacao-e-sequencia.md).
- Infraestrutura reaproveitável (sem a política): FN-024 (docs/77–99).
- Perfis e permissões: [docs/03](../../03-perfis-e-permissoes.md) — a matriz TR1 ainda não está no catálogo.
- Casos de uso: [casos-de-uso.md](casos-de-uso.md). Sem UC no docs/07.
- Mockups: não há mockup do módulo Transferências; exigir aprovação de UI/UX antes de UX1.

## 4. Dependências
| Depende de | Por quê | Situação |
|---|---|---|
| FN-016 identidade-pessoal | Pessoa única, CPF protegido, provisórios e conciliação (ID1 precede TE1) | Parcial |
| FN-026 matricula-enturmacao | Matrícula com intervalos; MT1 precede TE1 | Parcial |
| FN-031 catalogo-instituicoes | Instituições de referência para conciliação institucional de TE1 | Em homologação |
| FN-057 notificacoes | TR1-D e UX1-D são notificações; módulo transversal | Pendente (não listado no plano mestre; ver §8) |
| FN-024 copias-entre-espacos | Infraestrutura de lotes, outbox, staging cifrado e recibos | Substituída (código ativo) |

**Funcionalidades que dependem desta:** FN-027 (integração pessoal → escola usa a matriz TR), FN-028 (T6 reconcilia recibos e cessões), FN-064 (L4-D/E sobre pessoa/matrículas/recibos).

## 5. Fases e subfases
| Subfase | Entrega | Critério de pronto | Status | Evidência |
|---|---|---|---|---|
| 1.1 (TR1-A) Capacidades | Matriz solicitar/confirmar/autorizar/executar por contexto; remoção das capacidades genéricas T5 nesta política | Matriz aprovada no docs/03 e testada | Pendente | docs/115 §9 |
| 1.2 (TR1-B) Delegação | Execução delegada por caso, categoria e prazo a Suporte/Desenvolvedor | Suporte não lê outro caso nem aprova como instituição | Pendente | docs/115 §13 |
| 1.3 (TR1-C) Auditoria imutável | Eventos append-only encadeados; sem UPDATE/DELETE pela credencial da aplicação | Teste de imutabilidade e falha segura | Pendente | docs/115 §9 |
| 1.4 (TR1-D) Notificações | Eventos de solicitação, decisão, início, conclusão, rejeição via outbox | Usa FN-057 | Pendente | docs/115 §9 |
| 2.1 (TE1-A) Ingresso | Destino inicia por CPF/identidade provisória; consulta protegida de matrícula elegível | Sem enumeração | Pendente | docs/115 §8 |
| 2.2 (TE1-B) Cessão e corte | Solicitação com categorias e data de corte; decisão da origem | Rejeição/cancelamento não aparece como concluído | Pendente | docs/115 §8 |
| 2.3 (TE1-C) Snapshot e conciliação | Manifesto, snapshot versionado cifrado, novos IDs locais | Origem conserva passado; destino isolado | Pendente | docs/115 §8 |
| 2.4 (TE1-D) Receber e retificar | Retificação como delta versionado | Recibo finalizado imutável | Pendente | docs/115 §8 |
| 3.1 (UX1-A) Acompanhamento | Tabela de solicitações, filtros, estado e próximo passo | Revisão UI/UX aprovada | Pendente | docs/115 §9 |
| 3.2 (UX1-B) Transferir | Assistente tipo → origem/destino → itens → corte → prévia → autorizações → recibo | Idem | Pendente | docs/115 §9 |
| 3.3 (UX1-C) Receber | Pendências e formulário de ingresso | Idem | Pendente | docs/115 §9 |
| 3.4 (UX1-D) Notificações na interface | Usa FN-057 | Idem | Pendente | docs/115 §9 |

## 6. Contrato técnico (proposto, docs/115)
- **Estados:** rascunho → solicitado → aguardando_origem → aguardando_destino → autorizado → em_execucao → concluido; rejeitado, cancelado, expirado e bloqueado não são sucesso. Operação concluída não reabre.
- **Modelo híbrido:** registros de produção ficam na origem; histórico recebido é snapshot cifrado com proveniência no destino; nenhuma FK pedagógica atravessa espaços.
- **Idempotência:** chave de intenção; mesma chave não duplica pessoa, vínculo ou transferência.
- **Tempo:** `ocorrido_em` × `registrado_em`; lançamento tardio anterior ao corte gera retificação.
- **Migrations:** aditivas e retomáveis; expandir → mapear → conferir → trocar leituras → restringir.
- **Desempenho:** medir com o padrão de 1000 usuários simultâneos as telas de acompanhamento antes da homologação (FN-077).

## 7. Interface
Primeira página com tabela e até duas ações (Transferir, Receber); cada etapa mostra ator responsável, estado e próximo passo; sem manual longo na abertura. Estados de carga, vazio e erro conforme FN-076; tabela conforme FN-074. Não publicar o rótulo "Transferências" com as permissões antigas de T5.

## 8. Riscos e decisões pendentes do responsável
| Item | Opções | Recomendação | Decisão |
|---|---|---|---|
| §4 item 3 — política T5 × docs/115 | Declarar T5 substituída; flags `COPIAS_*` desligadas em produção até TR1; não renomear antes de TR1 | A do plano mestre | Pendente |
| §4 item 4 — aceite visual de T5 | Dispensar e fazer na UX1 | A do plano mestre | Pendente |
| §4 item 6 — matriz ID1 (destrava TR1/TE1) | Aprovar matriz | A do plano mestre | Pendente |
| §4 item 12 — contrato de conciliação de identidade | Escrever como subfase de FN-016 na Onda 1 | A do plano mestre | Pendente |
| Dependência de FN-057 (notificações) não consta no plano mestre (que lista só 016, 026, 031), embora TR1-D e UX1-D sejam notificações e a governança proíba versões locais | Acrescentar FN-057 às dependências / manter TR1-D sem notificação | Acrescentar no plano mestre | Pendente (divergência registrada) |
| Imutabilidade exige separação de credenciais do banco | — | Definir junto com FN-004 (revisão de privilégios) | Pendente |
| Regras contratuais de cessão e guarda por instituição | — | Não inventar prazos; registrar por contrato (FN-068) | Pendente |

## 9. Ramos desta entrega
Nenhum (não iniciada).

## 10. Histórico
| Data | Mudança | Por |
|---|---|---|
| 2026-11-09 | Documento criado a partir do inventário e do código | lucasgenial |
