# 028 — Encerramento de cliente (T6) — Planejamento

**Implementação:** Pendente
**Etapa:** Em planejamento
**Prioridade no plano mestre:** Onda 4 — Comercial, portal e encerramento (depende de FN-064 e FN-068)
**Natureza:** nova
**Origem:** tronco original — docs/28 Fase 3 (T6); docs/102; docs/115 §12 ("L4/T6 — reconciliação final")
**Atualizado em:** 2026-11-09, por lucasgenial

## 1. Objetivo
Encerrar o contrato de um cliente de forma controlada: prever o impacto, bloquear o acesso de modo reversível, entregar a exportação institucional, aplicar a retenção acordada e executar o encerramento sem afetar outros clientes nem a identidade global das pessoas.

## 2. Escopo
- **Inclui:** T6-A pedido e prévia; T6-B bloqueio reversível; T6-C exportação institucional e continuidade autorizada; T6-D retenção, preservação excepcional e simulação final; T6-E execução autorizada e conferência; ciclo de situação do cliente e dos espaços.
- **Não inclui:**
  - Exportação, correção e eliminação a pedido do titular (L4-D/E) → FN-064 (pré-requisito).
  - Contratos e cobrança → FN-068.
  - Cessão de histórico de alunos entre escolas → FN-025.
  - Continuidade do professor ao sair → FN-027 (PA1-D).
  - Política geral de retenção → FN-062.
  - Backups e restauração → FN-003.

## 3. Fontes de verdade
- Documentos de origem: docs/102 (`docs/102-planejamento-l4-t6.md`) (quadro T6-A..E), docs/115 §12 (`docs/115-replanejamento-provas-identidade-transferencias.md`), docs/40 §8, docs/71 ("situação do cliente continua sob T6").
- Perfis e permissões: docs/03 (`docs/03-perfis-e-permissoes.md`) — sem permissões de encerramento definidas.
- Casos de uso: [casos-de-uso.md](casos-de-uso.md).
- Mockups: não há.

## 4. Dependências
| Depende de | Por quê | Situação |
|---|---|---|
| FN-064 pedidos-titulares | L4-D/E (retenção, eliminação) antes de T6 | Parcial |
| FN-068 financeiro | Contrato e condição comercial do encerramento | Pendente |
| FN-023 cadastro-clientes | Cliente e seus espaços; situação hoje sempre `ativo` | Concluída |
| FN-026 matricula-enturmacao | Rever T6 sobre pessoa/matrículas (docs/115) | Parcial |
| FN-016 identidade-pessoal | Encerrar não apaga a identidade global | Parcial |
| FN-025 transferencias-institucionais | Cessões recebidas não são apagadas por cascade | Pendente |
| FN-062 lgpd-inventario-retencao | Prazos de guarda | Parcial |

**Funcionalidades que dependem desta:** FN-029 (encerramento posterior à assunção financeira), FN-068 (ciclo do contrato).

## 5. Fases e subfases
| Subfase | Entrega | Critério de pronto | Status | Evidência |
|---|---|---|---|---|
| 1.1 (T6-A) Pedido e prévia | Inventário de espaços, acessos, filas, exportações e cópias; distinguir cliente de usuário com outros vínculos | Prévia sem efeito colateral | Pendente | docs/102 |
| 1.2 (T6-B) Bloqueio reversível | Revalidar sessões, tokens e jobs do escopo encerrado | Outros clientes/vínculos seguem funcionando | Pendente | docs/102 |
| 1.3 (T6-C) Exportação institucional e continuidade | Contrato próprio de exportação; continuidade por decisão explícita | Não reutiliza a exportação do titular | Pendente | docs/102 |
| 1.4 (T6-D) Retenção e simulação final | Guarda por contrato, recibos, exceções | Rotina não inicia só por data vencida | Pendente | docs/102 |
| 1.5 (T6-E) Execução e conferência | Escopo final aprovado, recuperação ensaiada, backups tratados | Evidência de preservação dos demais espaços | Pendente | docs/102 |

## 6. Contrato técnico (proposto)
Estados de cliente e espaço (ativo → em encerramento → bloqueado → encerrado), reversíveis até a execução; `PredicadosVinculo` já filtra por situação de cliente e espaço; migrations aditivas; execução por job idempotente com recibo; nenhuma exclusão por cascade; eventos em `cliente_eventos` e na auditoria (FN-004). Nenhum schema, endpoint ou interface existe hoje.

## 7. Interface
Telas internas no contexto de Suporte/Financeiro (a partir do detalhe do cliente): pedido, prévia, bloqueio, acompanhamento. Aviso ao gestor do cliente. Revisão UI/UX obrigatória.

## 8. Riscos e decisões pendentes do responsável
| Item | Opções | Recomendação | Decisão |
|---|---|---|---|
| §4 item 11 — LGPD operacional: retenções, prazos abertos, política dos protocolos L4 | Pacote único na Onda 3 | A do plano mestre | Pendente |
| §4 item 14 — condições comerciais e regras financeiras | Tratar em FN-068 | A do plano mestre | Pendente |
| §4 item 2 — gate de produção ("nada antes de fechar L4" × ADR-H1) | Revogar o gate L4 | A do plano mestre | Pendente |
| Prazos legais de guarda | — | Conferir em fonte oficial; não inventar | Pendente |
| Tratamento de backups após o encerramento | — | Documentar em T6-E | Pendente |

## 9. Ramos desta entrega
Nenhum (não iniciada).

## 10. Histórico
| Data | Mudança | Por |
|---|---|---|
| 2026-11-09 | Documento criado a partir do inventário e do código | lucasgenial |
