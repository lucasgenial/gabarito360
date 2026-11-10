# 051 — TCT/TRI, integridade e sínteses — Planejamento

**Implementação:** Candidata
**Etapa:** Candidata
**Prioridade no plano mestre:** Futuro (candidatas sem prioridade, §5). Não executar antes da priorização do responsável.
**Natureza:** nova
**Origem:** docs/164 (frentes "Em breve" da apresentação pública) e docs/177 (P7.2–P7.4 dos painéis)
**Atualizado em:** 2026-11-09, por lucasgenial

## 1. Objetivo
Oferecer análises que vão além da nota e do percentual de acertos: medidas psicométricas (TCT/TRI), indícios de padrões anômalos de resposta e sínteses automáticas, sempre com limites de interpretação explícitos e revisão humana.

## 2. Escopo preliminar
- **Inclui (proposto no docs/177):**
  - P7.2 Medidas psicométricas: reconciliar o plano de TCT/TRI; definir escala, requisitos, população, versão do método e limites; apresentar separado de nota e percentual; comparar instrumentos só após validação.
  - P7.3 Integridade e mapa da sala: padrões anômalos, similaridade, distratores raros, mapa de sala, presença, análise de grupos; permissão própria, evidências e revisão humana.
  - P7.4 Sínteses automáticas (agentes).
- **Não inclui:** GII e habilidades (P7.1) → FN-044; relatórios atuais → FN-049; cards demonstrativos dos painéis → FN-053.

## 3. Fontes de verdade
docs/177 (`docs/177-plano-paineis-turmas.md`) (P7), docs/164 (`docs/164-apresentacao-publica-pre-lancamento.md`) (anúncio "Em breve"), docs/12 (`docs/12-arquitetura.md`) (motores futuros). Sem mockup próprio. Casos de uso propostos: [casos-de-uso.md](casos-de-uso.md).

## 4. Dependências
| Depende de | Por quê | Situação |
|---|---|---|
| FN-049 relatorios-resultados | Respostas e notas por questão | Concluída |
| FN-043 editor-provas-questoes / FN-044 acervo-gii | Itens versionados e habilidades para calibrar TRI | Candidata |
| FN-053 paineis-turmas | Pontos de integração (cards hoje demonstrativos) | Parcial |
| FN-062 lgpd-inventario-retencao | Integridade trata dados sensíveis de comportamento | Parcial |

**Funcionalidades que dependem desta:** FN-053 e FN-055 (cards de análises avançadas hoje "Em breve").

## 5. Fases e subfases
| Subfase | Entrega | Critério de pronto | Status | Evidência |
|---|---|---|---|---|
| 1.1 Plano | Documentos aprovados | Aprovação do responsável | Pendente | §3 do plano mestre ("Plano") |
| 2.1 (P7.2) Medidas psicométricas | — | — | Pendente | docs/177 |
| 2.2 (P7.3) Integridade e mapa da sala | — | — | Pendente | docs/177 |
| 2.3 (P7.4) Sínteses automáticas | — | — | Pendente | docs/177 |

## 6. Contrato técnico
A definir. Regra já registrada no docs/177: não reaproveitar a verificação de arquivo/PDF (FN-047) como prova de que o motor de integridade existe; converter para percentual não torna provas equivalentes.

## 7. Interface
A definir. Até a priorização, os cards permanecem "Em breve" com dados demonstrativos (decisão do docs/180).

## 8. Riscos e decisões pendentes do responsável
| Item | Opções | Recomendação | Decisão |
|---|---|---|---|
| §4 item 1 — aprovação do plano mestre e da priorização | — | Manter em "Futuro" | Pendente |
| §4 item 13 — frentes "Em breve" da home sem plano (TCT/TRI, integridade) | Manter "Em breve" só para o priorizado; registrar as demais como candidatas | Seguir a recomendação do §4 (já registrada como FN-051) | Pendente |
| Uso de agentes para sínteses (P7.4) | — | Definir no plano, com revisão humana | Pendente |

## 9. Ramos desta entrega
Nenhum.

## 10. Histórico
| Data | Mudança | Por |
|---|---|---|
| 2026-11-09 | Documento criado a partir do inventário e do código | lucasgenial |
