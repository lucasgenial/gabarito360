# 059 — Marcação e aplicação de provas — Planejamento

**Implementação:** Pendente
**Etapa:** Em planejamento
**Observação de estado:** aguarda FN-056 e FN-057
**Prioridade no plano mestre:** Onda 2 (PROPOSTA do §5), depende de 056 e 057
**Natureza:** evolução de existente (a data de aplicação da prova e as consultas derivadas existem; o agendamento como evento, os avisos e o registro de aplicação não existem)
**Origem:** tronco original — docs/28 C4 (Fase 7); docs/177 P1.4.6, P5.4 e P3.3.5. Equivalência: C4 → FN-059 ([plano mestre §1.2](../../planejamento/00-plano-mestre.md))
**Atualizado em:** 2026-11-09, por lucasgenial

## 1. Objetivo
Transformar a data de aplicação de cada prova em um compromisso real da agenda única, com avisos à coordenação, direção e núcleo e lembrete na véspera, e registrar a aplicação propriamente dita: quem era elegível, quem participou e o que aconteceu com cada estudante. Atende quem marca provas (professor, coordenação), quem aplica (aplicador) e quem acompanha (direção, núcleo, painéis).

## 2. Escopo
- **Inclui:**
  - C4: criar o evento ao agendar uma aplicação, atualizá-lo ao mudar a data e cancelá-lo ao excluir ou desmarcar, de forma idempotente (P5.4.1); avisos à coordenação, direção e núcleo e lembrete na véspera pelo agendador, via FN-057.
  - Registro de aplicação e elegíveis (P1.4.6, P1.4.7, P3.3.5): conjunto de estudantes esperados por aplicação com referência histórica; presença confirmada, cartão recebido, cartão processado, resultado válido e ausência; política de dispensa, transferência, ingresso tardio e reaplicação.
  - Migrar as listas "próximas provas/agenda" dos dashboards por perfil e o recorte "provas do dia" do aplicador para lerem a mesma fonte, quando o evento existir.
- **Não inclui:**
  - Modelo de eventos, tela de calendário e cards → [FN-056](../056-agenda-calendario/planejamento.md). **Proibido criar calendário de provas próprio.**
  - Motor, central e preferências de aviso → [FN-057](../057-notificacoes/planejamento.md).
  - Modelos de e-mail `prova-agendada`, `prova-lembrete`, `correcao-concluida`, `correcao-fechada` → [FN-060](../060-email-transacional/planejamento.md) (E4 fase 4).
  - Intervalos de matrícula e contexto histórico → [FN-026](../026-matricula-enturmacao/planejamento.md) (MT1), necessários para elegibilidade.
  - Taxa de participação e cobertura nos painéis → [FN-053](../053-paineis-turmas/planejamento.md) e [FN-039](../039-periodos-letivos/planejamento.md) (P4.4).
  - Criação e edição da prova e do gabarito → [FN-040](../040-provas-gabaritos/planejamento.md).

## 3. Fontes de verdade
- Mockups: `mockups/dashboard-diretor-escolar.html` (Calendário de provas: aplicações agendadas com status), `mockups/dashboard-coordenador.html` (Agenda da semana), `mockups/criar-prova.html` (Passo 3 "Cartão & Turmas", não detalhado); inventário em docs/01 (`docs/01-inventario-dos-mockups.md`).
- Documentos de origem: docs/28 (`docs/28-plano-mestre-proximos-modulos.md`) C4 e E4; docs/177 (`docs/177-plano-paineis-turmas.md`) P1.4.6, P1.4.7, P3.3.5, P5.4; docs/03 (`docs/03-perfis-e-permissoes.md`) (aplicador: provas do dia da sua escola).
- Casos de uso: [casos-de-uso.md](casos-de-uso.md); UC-003 do docs/07 (`docs/07-casos-de-uso.md`) (criar prova) e UC-007 (dashboards) são relacionados.

## 4. Dependências
| Depende de | Por quê | Situação |
|---|---|---|
| FN-056 agenda-calendario (C2 e contrato de integração) | O evento da aplicação vive na agenda única | Pendente |
| FN-057 notificacoes (N1, N3) | Avisos e lembrete da véspera | Pendente |
| FN-026 matricula-enturmacao (MT1) | Elegíveis por aplicação com histórico de matrícula | Parcial |
| FN-060 email-transacional (E4 fase 4) | Modelos de e-mail de prova agendada e lembrete | Parcial |
| FN-040 provas-gabaritos | Prova, `data_aplicacao`, `prova_turmas` | Concluída |
| FN-002 base-operacao (B4 agendador) | Lembrete na véspera | Concluída |

**Funcionalidades que dependem desta:** FN-053 (P1.4.6 elegíveis por aplicação; P3.3); FN-060 (E4 fase 4); FN-039 (P4.4, por meio de FN-026).

## 5. Fases e subfases
| Subfase | Entrega | Critério de pronto | Status | Evidência |
|---|---|---|---|---|
| 0.1 (MP-019/MP-020) Data de aplicação e leituras derivadas | Campo `provas.data_aplicacao`; "provas do dia" do aplicador; listas de próximas provas nos dashboards | — | Entregue (produção) | `2026_01_01_000008_create_provas_table.php`; `ProvaController` (validação `data_aplicacao`); `Escopo::consultarProvas`; `ConsultaVinculo` (aplicador); `DashboardController` (aluno, professor, coordenador, dirEscolar, admin, aplicador) |
| 1.1 (C4, P5.4.1) Provas no calendário | Evento criado, atualizado e cancelado a partir da aplicação, idempotente | Testes de criação, mudança, cancelamento e repetição sem duplicar | Pendente | — |
| 1.2 (C4, P5.4.2–P5.4.5) Avisos e lembrete | Notificação à coordenação, direção e núcleo; lembrete na véspera; agenda funciona antes dos avisos | Testes de destinatários e de não duplicidade do lembrete | Pendente | — |
| 2.1 (P1.4.6, P1.4.7, P3.3.5) Registro de aplicação e elegíveis | Captura do conjunto esperado por aplicação e dos estados de participação | Elegibilidade histórica confiável; participação não presumida | Pendente | — |
| 2.2 Migração das leituras derivadas | Dashboards e aplicador leem a aplicação/evento | Sem divergência entre agenda, dashboards e recorte do aplicador | Pendente | — |

## 6. Contrato técnico (proporcional ao risco)
- API dona do banco; migrations aditivas (registro de aplicação e elegíveis).
- Sincronização prova → evento pelo serviço de FN-056 com `origem_tipo=aplicacao`; idempotência por prova e turma (a definir se uma prova com várias turmas gera um ou vários eventos).
- Lembrete pelo agendador (B4), com chave única por aplicação e data para não reenviar.
- Destinatários revalidados no envio (FN-057).
- Fuso: definir explicitamente o fuso da instituição para "hoje" e "véspera" (ver §8).
- Desempenho: consultas de "provas do dia" e de elegíveis medidas com o padrão de 1000 usuários simultâneos.

## 7. Interface
Agenda e cards de FN-056; campo de data no formulário da prova (FN-040); visão do aplicador "provas de hoje"; registro de presença/ausência (tela a desenhar, sem mockup). Estados carregando, vazio e erro; desktop e 375 px; conferência por texto.

## 8. Riscos e decisões pendentes do responsável
| Item | Opções | Recomendação | Decisão |
|---|---|---|---|
| §4 item 1 — aprovação do plano mestre e da priorização | Aprovar / ajustar | Aprovar §1–§3 e a Onda 0 | Pendente |
| §4 item 7 — escopo da agenda e PoC (C1) | Aprovar / alternativa | Aprovar a PoC e priorizar FN-039 com FN-056 | Pendente |
| ⚠ Fuso de "hoje" do aplicador | O recorte usa data UTC (`RelogioConsulta::hojeUtc()` em `ConsultaVinculo`; `now()` com `app.timezone=UTC` em `Escopo` e no dashboard), enquanto o histórico usa `hojeSaoPaulo()` | Usar o fuso da instituição; entre 21h e 24h (horário de Brasília) o aplicador hoje vê as provas do dia seguinte | Pendente (divergência de código, não registrada no plano mestre) |
| Um evento por prova ou por prova × turma | — | Decidir na 1.1 com base nas turmas com horários distintos | Pendente |
| Dashboards já exibem "agenda" derivada de `data_aplicacao` | Manter até 2.2 | Manter como leitura derivada; não criar armazenamento de eventos paralelo | Registrado |
| Elegibilidade sem MT1 | Aguardar FN-026 / capturar antes | docs/177 P3.3.5 permite antecipar a captura de novas aplicações quando o contrato P1.4.6 for validado | Pendente |

## 9. Ramos desta entrega
Nenhum aberto.

## 10. Histórico
| Data | Mudança | Por |
|---|---|---|
| 2026-11-09 | Documento criado a partir do inventário e do código | lucasgenial |
