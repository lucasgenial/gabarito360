# 039 — Períodos letivos — Planejamento

**Estado no tronco:** Candidata
**Prioridade no plano mestre:** aguardando priorização; a PROPOSTA de ondas (§5) a coloca na Onda 1, ordem 3 (P4.1–P4.3), e o §4 item 7 recomenda priorizá-la junto com FN-056
**Natureza:** nova (só existe o widget demonstrativo dos painéis)
**Origem:** surgiu como ramo da entrega dos painéis de turmas (docs/177 P4, docs/179, docs/180; identificador local "PL1")
**Atualizado em:** 2026-11-09, por agente de documentação

## 1. Objetivo
Modelar o calendário acadêmico (ano/ciclo, unidades, bimestres, trimestres, semestres, organização própria da EJA) por escola, turma ou espaço pessoal, para que avaliações, painéis e acompanhamento possam ser recortados por período pedagógico em vez de intervalo de datas.

## 2. Escopo preliminar
- **Inclui:** modelo acadêmico (P4.1); configuração, versão e histórico (P4.2); integração com avaliações e painéis, ativando o seletor já desenhado (P4.3); elegibilidade e participação por aplicação (P4.4).
- **Não inclui:**
  - Eventos, agenda e calendário visual → FN-056 (módulo transversal; não criar agenda própria aqui).
  - Intervalos de matrícula e histórico de enturmação → FN-026 (MT1), pré-requisito de P4.4.
  - Vínculo temporal área–disciplina (não é período letivo) → FN-038.
  - Cards e recortes dos painéis → FN-053; acompanhamento pedagógico → FN-055.

## 3. Fontes de verdade
- Documentos de origem: [docs/177](../../177-plano-paineis-turmas.md) fase P4 (P4.1.1–P4.4.5); docs/179, docs/180.
- Widget demonstrativo "Resultados por período escolar" (PR #211–#214, #226).
- Casos de uso: [casos-de-uso.md](casos-de-uso.md).
- Perfis e permissões: [docs/03](../../03-perfis-e-permissoes.md) — chaves a definir (P4.2.1: quem configura, revisa, encerra e reabre).
- Plano mestre: [§2 FN-039](../../planejamento/00-plano-mestre.md).

## 4. Dependências
| Depende de | Por quê | Situação |
|---|---|---|
| FN-056 agenda-calendario (C2) | Modelo de eventos e calendário compartilhado | Pendente |
| FN-038 disciplinas-curriculo | Ofertas e componentes por turma | Em homologação |
| FN-026 matricula-enturmacao (MT1-A..C) | Elegibilidade e participação (P4.4) | Parcial |

**Funcionalidades que dependem desta:** FN-055 acompanhamento-pedagogico (aguarda FN-056 e FN-039); FN-053 paineis-turmas (recorte por período); FN-056 (recorte por período da agenda, segundo o §5).

## 5. Fases e subfases
| Subfase | Entrega | Critério de pronto | Status | Evidência |
|---|---|---|---|---|
| 0.1 | Widget demonstrativo "Resultados por período escolar" | Visual sem dados reais | Entregue (homologação, visual) | PR #211–#214, #226 |
| 1.1 (P4.1) | Modelo acadêmico | Calendários distintos na mesma escola e no espaço pessoal | Pendente | — |
| 1.2 (P4.2) | Configuração, versão e histórico | Sobreposição validada; "Sem período definido" | Pendente | — |
| 1.3 (P4.3) | Integração com avaliações e painéis | Seletor ativo; sem dupla contagem | Pendente | intervalo de datas segue funcional |
| 1.4 (P4.4) | Elegibilidade e participação | Após MT1 | Pendente | — |

## 6. Contrato técnico (preliminar)
- Migrations aditivas para ciclos e períodos (nome, ordem, início, fim, situação, relação pai/filho) associados a escola/turma/espaço.
- Não converter datas em períodos silenciosamente; associação de registros antigos com prévia de impacto (P4.2.4).
- Separar período pedagógico, data de aplicação e data de correção (P4.3.1).
- Desempenho dos recortes conforme FN-077.

## 7. Interface
Seletor de período nos painéis (já desenhado); tela de configuração do calendário a desenhar e aprovar.

## 8. Riscos e decisões pendentes do responsável
| Item | Opções | Recomendação | Decisão |
|---|---|---|---|
| §4 item 7 — escopo do módulo de agenda e PoC do CalendarJS (C1); FN-039 ainda candidata | Aprovar PoC e priorizar FN-039 com FN-056 / aguardar | Aprovar a PoC com os critérios do docs/28 e priorizar FN-039 junto com FN-056 | Pendente |
| §4 item 1 — aprovação do plano mestre e da priorização | Aprovar / ajustar | Aprovar §1–§3 e Onda 0 | Pendente |
| Reserva do identificador "PL1" (pedido no inventário G6) | — | Usar FN-039 como identificador canônico | Registrado |

## 9. Ramos desta entrega
Nenhum.

## 10. Histórico
| Data | Mudança | Por |
|---|---|---|
| 2026-11-09 | Documento criado a partir do inventário e do código | agente de documentação |
