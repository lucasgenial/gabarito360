# 024 — Cópias entre espaços (T5) — Planejamento

**Estado no tronco:** Substituída (política); código ativo
**Prioridade no plano mestre:** Onda 0 — decisões do §4 itens 3 e 4. Não há execução nova prevista; a política nova é FN-025 (Onda 3).
**Natureza:** existente documentada a partir do código
**Origem:** tronco original — docs/28 Fase 3 (T5); docs/77–99, 114; substituição pelo docs/115 §9
**Atualizado em:** 2026-11-09, por lucasgenial

> Documento curto de funcionalidade substituída. Não decide o destino do código: as decisões estão no §4 do [plano mestre](../../planejamento/00-plano-mestre.md).

## 1. Objetivo (original) e motivo da substituição
T5 implementou cópias independentes de materiais, cadastros e resultados entre espaços, com autorização bilateral, prévia, evidência cifrada e recibo. Em 03/11/2026 o docs/115 §1 registrou que o pedido original era **duplicar prova no mesmo espaço** (atendido por FN-042, CP1) e que o mecanismo de T5 seria reposicionado em **Transferências** (FN-025), com nova matriz de autoridade (TR1). A política T5 — capacidades genéricas `copia.autorizar_origem/destino` do Administrador da Rede e do Diretor Escolar — foi declarada substituída.

## 2. O que existe hoje
- **Código mesclado e publicado:** T5-A1..E3 (capacidades, lotes, outbox, materiais, cadastro, resultados e interface), PR #111–#132, em produção desde 02/11 com as flags ativas; **nenhuma cópia foi iniciada** (docs/100). Revisão UX (PR #149) e flags ligadas só na homologação (docs/114, 129).
- **Flags (`apps/api/config/copias.php`, padrão `false`):** `COPIAS_ATIVAS`, `COPIAS_INTERFACE_ATIVA`, `COPIAS_AUDITORIA_ENTREGA`, `COPIAS_EXECUCAO_ATIVA`, `COPIAS_CADASTRO_EXECUCAO_ATIVA`, `COPIAS_RESULTADOS_EXECUCAO_ATIVA`; na web, `services.copias.interface_ativa`.
- **Permissões:** `copia.autorizar_origem` e `copia.autorizar_destino` padrão de `admin_rede`, `dir_escolar` e `professor_autonomo` (`CatalogoPermissoes`).
- **Rotas:** API `/api/v1/copias*` (acompanhamento, seleção e prévia de materiais/cadastros/resultados, recebimentos, lotes, evidência, aprovação, operação, acervo); web `/app/copias*`; menu "Cópias".
- **Processos:** `g360:copias-executar` e `g360:copias-auditoria` (timer de publicadores na homologação).

## 3. O que a substitui
| Necessidade | Funcionalidade |
|---|---|
| Duplicar prova no mesmo espaço (pedido original) | FN-042 (CP1 entregue na homologação; CP2 pendente) |
| Cessão/recebimento entre espaços e escolas, com autoridade institucional | FN-025 (TR1, TE1, UX1) |
| Matrícula e enturmação temporal | FN-026 (MT1) |
| Continuidade do professor ao sair da escola | FN-027 (PA1-D) |
| Exportação de dados do titular | FN-064 |

## 4. Dependências
| Depende de | Por quê | Situação |
|---|---|---|
| FN-021, FN-022, FN-015 | Espaços, espaço pessoal, vínculos | Concluídas |
| FN-004 auditoria (AU1) | Envelope de domínio e outbox | Parcial (AU1 entregue) |

**Funcionalidades que dependem desta:** FN-025 pode reaproveitar infraestrutura (lotes, outbox, staging cifrado, recibos), sem herdar a política (docs/115 §1 e §11).

## 5. Fases e subfases
| Subfase | Entrega | Status | Evidência |
|---|---|---|---|
| T5-A1..E3 (capacidades, lotes, outbox, materiais, cadastro, resultados, interface) | Código completo | Entregue (produção 02/11, flags ativas; nenhuma cópia iniciada) | PR #111–#132; docs/100 |
| Revisão UX e flags na homologação | Entrada, prévias e recebimento reorganizados | Entregue (homologação) | PR #149; docs/114, 129 |
| Aceite visual E1–E3 | Conferência pelo responsável | Pendente (§4 item 4 recomenda dispensar) | docs/99, 100 |
| Política antiga (capacidades genéricas do Diretor Escolar) | — | Substituída por FN-025 | docs/115, 117 |
| Complemento de nascimento, solicitação do professor, estatística anônima, limpeza de staging/outbox, ensaio de vazão no servidor | — | Pendente — ou encerrar em favor de FN-025 | docs/77–90 |

## 6. Contrato técnico (resumo do existente)
Elegibilidade (`ElegibilidadeCopia`) exige `copias.ativo`, vínculo explícito e escopo; lotes com decisões persistidas, idempotência e transação `SERIALIZABLE`; outbox transacional `copias_outbox` com envelope na auditoria AU1; staging cifrado e publicação por unidade; evidências cifradas; código de destinatário de 256 bits com validade de 15 minutos; acervo separado de resultados.

## 7. Interface
Menu "Cópias" (visível com `copia.autorizar_*` e flag de interface). Aceite visual pendente; o plano mestre recomenda fazê-lo na UX1 (FN-025).

## 8. Riscos e decisões pendentes do responsável
| Item | Opções | Recomendação | Decisão |
|---|---|---|---|
| §4 item 3 — política T5 × docs/115: T5 ativo em produção com a política antiga | Declarar substituída; flags `COPIAS_*` desligadas em produção até TR1; não renomear para "Transferências" antes de TR1 | A do plano mestre | Pendente |
| §4 item 4 — aceite visual E1–E3 | Fazer agora / dispensar e fazer na UX1 | Dispensar e fazer na UX1 (FN-025) | Pendente |
| §4 item 2 — gate de produção (a revisão UX PR #149 está só na homologação, §7) | — | Conforme decisão do item 2 | Pendente |
| Pendências T5 (complemento de nascimento, solicitação do professor, estatística anônima, limpeza de staging/outbox, ensaio de vazão) | Executar / encerrar em favor de FN-025 | Encerrar em favor de FN-025 (§3 do plano mestre) | Pendente |

## 9. Ramos desta entrega
Nenhum ativo.

## 10. Histórico
| Data | Mudança | Por |
|---|---|---|
| 2026-11-09 | Documento criado a partir do inventário e do código | lucasgenial |
