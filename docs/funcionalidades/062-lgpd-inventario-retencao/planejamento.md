# 062 — Inventário de dados e retenção — Planejamento

**Implementação:** Parcial
**Etapa:** Em planejamento
**Conforme princípios:** versão 1
**Prioridade no plano mestre:** Onda 3 (proposta), dentro do "pacote de retenção" junto com FN-064, FN-066 e o expurgo de FN-004
**Natureza:** existente documentada a partir dos registros (inventário e RIPD); retenção aprovada ainda sem código
**Origem:** tronco original — docs/28 Fase 2, pacote L2; requisitos RL2, RL6, RL12 e RL13 do docs/33
**Atualizado em:** 2026-11-09, por lucasgenial

## 1. Objetivo
Manter o registro das operações com dados pessoais (finalidade, base, local, prazo e terceiros), aplicar a política de retenção aprovada e dar transparência sobre suboperadores e transferência internacional. Serve ao responsável pela plataforma, às instituições controladoras e aos titulares.

## 2. Escopo
- **Inclui:** inventário O01–O18 (docs/34); RIPD preliminar do portal (docs/38); decisões de L2 (docs/39); implementação das retenções aprovadas (logs 30 dias, jobs falhos 14 dias, resultado detalhado B6 30 dias, recibo mínimo B6 90 dias, tokens vencidos em 24 h, contatos sem contrato 6 meses); definição dos prazos ainda abertos; lista pública de suboperadores (RL12); validação do RIPD pelo controlador (RL13); contratos de terceiros; confirmação da criptografia do disco D: dos backups.
- **Não inclui:** eliminação e anonimização por titular (FN-064, L4-D/E); expurgo dos registros de auditoria (FN-004); eliminação das fotos de alunos (FN-066, passo 6); avaliação residual do portal após os controles (FN-067, P7); publicação do aviso de privacidade (FN-061).

## 3. Fontes de verdade
docs/28 L2 (`docs/28-plano-mestre-proximos-modulos.md`), docs/33 §6 (`docs/33-lgpd-privacidade-e-consentimento.md`), docs/34 (`docs/34-inventario-de-dados.md`), docs/38 (`docs/38-ripd-preliminar-portal.md`), docs/39 (`docs/39-validacoes-l2.md`), [docs/operacao/backup.md](../../operacao/backup-e-restauracao.md). Código: `apps/api/routes/console.php` (agendador sem rotinas de retenção). Sem mockup. Casos de uso: [casos-de-uso.md](casos-de-uso.md).

## 4. Dependências
| Depende de | Por quê | Situação |
|---|---|---|
| Decisão §4 item 11 (LGPD operacional) | Aprovar o pacote de retenção, prazos abertos e confirmação do disco D: | Pendente |
| FN-002 B6 | Retenção do resultado e do recibo de B6 incide sobre tabelas de B6 | Concluída (pendência de retenção registrada em FN-002) |
| Contratos de cada instituição | Prazo de dados escolares e cartões é por contrato (docs/39 §2) | Pendente; ligado a FN-023/FN-068 |

**Funcionalidades que dependem desta:** FN-067 portal dos responsáveis (o docs/28 bloqueia o portal antes de L2); FN-061 (links de suboperadores e de retenção no aviso); FN-064 (L4-D usa os prazos aprovados); FN-002 (retenção do recibo B6).

## 5. Fases e subfases
| Subfase | Entrega | Critério de pronto | Status | Evidência |
|---|---|---|---|---|
| 2.1 (L2 passos 1–2) | Inventário de operações e terceiros | Registro O01–O18 publicado | Entregue (documento) | PR #68; docs/34 |
| 2.2 (L2 passo 4) | RIPD preliminar do portal e dados de menores | R01–R11 avaliados | Entregue (documento, preliminar) | PR #71; docs/38 |
| 2.3 (L2 validações) | Decisões do responsável e conferência técnica de produção | Políticas aprovadas registradas | Entregue (documento) | PR #71; docs/39 |
| 2.4 | Implementar as retenções aprovadas no docs/39 §2 | Rotinas agendadas, testadas, com exceções justificadas | Pendente | nenhum comando de retenção em `apps/api/app/Console/Commands` |
| 2.5 | Definir prazos abertos (auditoria, fiscal, contas sem vínculo, arquivos de importação/CSV, caixas de e-mail) | Prazos aprovados e registrados | Pendente | docs/34, docs/39 §2 |
| 2.6 (L2 passo 3) | Contratos de terceiros e transferência internacional | Evidência de modalidade contratual por fornecedor | Pendente | docs/39 §6 |
| 2.7 (RL12) | Lista pública de suboperadores | Página ou seção pública referenciada pelo aviso | Pendente | — |
| 2.8 (RL13) | Validação do RIPD pelo controlador | Assunção registrada | Pendente | docs/38 §8 |
| 2.9 | Confirmar criptografia do disco D: dos backups | Conferência administrativa registrada | Pendente (inconclusiva) | docs/39 §5 |

## 6. Contrato técnico (proporcional ao risco)
- Rotinas de retenção como comandos Artisan agendados em `routes/console.php`, idempotentes, com execução em lotes e registro agregado (contagens), sem imprimir dados pessoais.
- Exceções (suporte, incidente, preservação) exigem motivo, responsável e data de revisão (docs/39 §2).
- O recibo mínimo B6 precisa de marco inequívoco para os 90 dias e não pode ser apagado enquanto necessário a uma retentativa.
- Tokens Sanctum sem expiração não ganham vencimento por idade (decisão do docs/39 §1).
- Restauração de backup reintroduz dados já eliminados: definir procedimento de reaplicação (risco R07 do docs/38).

## 7. Interface
Sem tela prevista nesta funcionalidade, exceto a lista pública de suboperadores (2.7), que segue o layout público.

## 8. Riscos e decisões pendentes do responsável
| Item | Opções | Recomendação | Decisão |
|---|---|---|---|
| §4 item 11 — LGPD operacional | Aprovar retenções do docs/39, prazos abertos, retenção dos protocolos L4, expurgo de AU1, confirmação do disco D: | Aprovar como um pacote único de retenção na Onda 3 | Pendente |
| Correio da equipe em Gmail pessoal | Manter com análise contratual; migrar | Sem recomendação registrada no plano mestre (docs/39 §6) | Pendente |
| Divergência documental | docs/34 §4 descreve estado anterior a L3/L6 (fotos aceitas, CPF sem cifragem) | Tratar conforme §6 do plano mestre (L3/L6 entregues) | Registrada no §6 |

## 9. Ramos desta entrega
Nenhum aberto.

## 10. Histórico
| Data | Mudança | Por |
|---|---|---|
| 2026-11-09 | Documento criado a partir do inventário e do código | lucasgenial |
