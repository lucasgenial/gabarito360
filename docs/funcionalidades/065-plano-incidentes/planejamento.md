# 065 — Plano de incidentes — Planejamento

**Estado no tronco:** Pendente
**Prioridade no plano mestre:** trilha paralela de baixo risco da Onda 1 (proposta), se o responsável aceitar: "FN-065 (plano de incidentes, só documento)"
**Natureza:** nova (documento operacional, sem código de produto)
**Origem:** tronco original — docs/28 Fase 2, pacote L5; requisito RL11 do docs/33
**Atualizado em:** 2026-11-09, por agente de documentação

## 1. Objetivo
Ter um procedimento de resposta a incidentes de segurança com dados pessoais: como detectar, registrar, conter, avaliar, comunicar à ANPD e aos titulares quando cabível, e aprender com o ocorrido. Atende o responsável pela plataforma e as instituições controladoras.

## 2. Escopo
- **Inclui:** `docs/operacao/incidentes.md` com registro de incidentes, papéis e responsáveis, critérios de gravidade, modelos de aviso (instituição, titulares, ANPD), prazos (considerar três dias úteis da ANPD após confirmar a norma), uso da auditoria AU1 como fonte de investigação, e uma simulação registrada.
- **Não inclui:** ferramenta automatizada de detecção ou alerta (FN-005 S1/AU3 ou candidata nova); notificações dentro do sistema (FN-057); revisão final de segurança do portal (FN-067, P7), que consome este plano; incidentes operacionais sem dados pessoais, que seguem o registro já existente em `docs/operacao/` (exemplo: incidente do painel do professor de 01/11/2026).

## 3. Fontes de verdade
[docs/28 L5](../../28-plano-mestre-proximos-modulos.md), [docs/33 §6](../../33-lgpd-privacidade-e-consentimento.md) (RL11), [docs/38](../../38-ripd-preliminar-portal.md) (riscos R01–R11), [docs/operacao/auditoria.md](../../operacao/auditoria.md), [docs/operacao/backup.md](../../operacao/backup.md), [docs/operacao/protecao-dados.md](../../operacao/protecao-dados.md), [docs/operacao/incidente-painel-professor-2026-11-01.md](../../operacao/incidente-painel-professor-2026-11-01.md) (registro isolado, sem dados pessoais). Sem mockup. Casos de uso: [casos-de-uso.md](casos-de-uso.md).

## 4. Dependências
| Depende de | Por quê | Situação |
|---|---|---|
| FN-004 auditoria (AU1) | Fonte de evidência da investigação | AU1 concluído; L5 desbloqueado |
| FN-061 (canal do encarregado) | Modelos de aviso citam o encarregado | Parcial (D13) — o plano pode usar campo a preencher |

**Funcionalidades que dependem desta:** FN-067 portal (P7 inclui plano de resposta a incidentes); FN-062 (validação do RIPD exige resposta a incidentes, docs/38 §8).

## 5. Fases e subfases
| Subfase | Entrega | Critério de pronto | Status | Evidência |
|---|---|---|---|---|
| 5.1 (L5) | `docs/operacao/incidentes.md`: registro, papéis, gravidade, modelos e prazos | Documento aprovado pelo responsável | Pendente | arquivo inexistente |
| 5.2 (L5) | Simulação registrada | Simulação com dados fictícios registrada; RL11 atendido | Pendente | — |

## 6. Contrato técnico (proporcional ao risco)
Sem código. O registro de incidentes não deve conter dados pessoais além do necessário; evidências ficam em local restrito, fora do Git. Sem prints.

## 7. Interface
Não se aplica.

## 8. Riscos e decisões pendentes do responsável
| Item | Opções | Recomendação | Decisão |
|---|---|---|---|
| Aceitar FN-065 como trilha paralela da Onda 1 (§5) | Executar agora ou na Onda 4 com o portal | Trilha paralela de baixo risco (§5) | Pendente (§4 item 1) |
| Prazo de comunicação à ANPD | Confirmar a norma vigente antes de fixar | Confirmar na escrita de 5.1 | Pendente |
| Dados da empresa e encarregado nos modelos | Aguardar D13 ou usar campos a preencher | Campos a preencher, como no docs/35 | Pendente (§4 item 9) |

## 9. Ramos desta entrega
Nenhum.

## 10. Histórico
| Data | Mudança | Por |
|---|---|---|
| 2026-11-09 | Documento criado a partir do inventário e do código | agente de documentação |
