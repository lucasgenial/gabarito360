# Registro de Execução - B7 (Comunicação e Tempo Real)

## Status: Concluído

Passo **B7** do [`21-plano-backend.md`](21-plano-backend.md) implementado e
verificado (suíte completa verde: 230 testes / 1190 asserções). Entrega
notificações, agenda, feed de atividade e os eventos Reverb do mockup.

---

## 1. Tabelas e models

Migration aditiva `2026_06_15_110000_create_communication_tables.php`
(`preferencias_notificacao` já existia desde a fundação de pessoas/preferências):

| Tabela | Papel |
|---|---|
| `notificacoes` | Notificações in-app (sino do shell), com escopo e estado de leitura |
| `eventos_agenda` | Eventos/visitas/aplicações da agenda, escopados (núcleo/escola/turma) |
| `participantes_eventos` | Participação e confirmação de presença por usuário |
| `atividades_recentes` | Feed de atividade dos painéis (append-only) |

Models: `Notificacao`, `EventoAgenda`, `ParticipanteEvento`, `AtividadeRecente`.
Check constraints de status/período aplicados via DDL.

## 2. Eventos em tempo real (Reverb)

Seis classes novas em `app/Events/`, seguindo o padrão de
`ApplicationProgressUpdated` (`ShouldBroadcast` + `ShouldDispatchAfterCommit`):

| Evento | `broadcastAs` | Canal | Disparado em |
|---|---|---|---|
| `ReadingReviewRequired` | `reading.review.required` | `applications.{id}` | captura com pendência |
| `ReadingConfirmed` | `reading.confirmed` | `applications.{id}` | confirmação de leitura |
| `ResultCalculated` | `result.calculated` | `applications.{id}` | cálculo do resultado |
| `ReportReady` | `report.ready` | `users.{id}` | relatório/exportação concluídos |
| `NotificationCreated` | `notification.created` | `users.{id}` | criação de notificação |
| `CalendarEventChanged` | `calendar.event.changed` | `escolas.{id}`/`nucleos.{id}` | criar/editar/cancelar evento |

`application.progress.updated` (B5) permanece. Todos os canais são privados e
escopados em [`routes/channels.php`](../../backend/routes/channels.php)
(`users.{id}`, `applications.{id}`, `escolas.{id}`, `nucleos.{id}`),
autorizados via `PortalScope` (`canViewSchool`, `canViewNucleo`).

## 3. Recursos API

| Método | Rota | Descrição |
|---|---|---|
| GET | `/notificacoes` (`?nao_lidas=1`) | Lista do ator; `meta.nao_lidas` (contador do sino) |
| POST | `/notificacoes/{n}/ler`, `/notificacoes/ler-todas` | Marca como lida(s) |
| GET/PUT | `/notificacoes/preferencias` | Preferências por evento × canal (sistema/email/push) |
| GET | `/agenda` (`?de=&ate=&escola_id=&turma_id=`) | Eventos no escopo do ator |
| POST | `/agenda` | Cria evento + participantes (idempotente, escopo autorizado) |
| GET/PUT | `/agenda/{evento}` | Detalhe / edição (`calendar.event.changed`) |
| POST | `/agenda/{evento}/confirmar` | Participante confirma presença |
| GET | `/atividades-recentes` (`?escola_id=`) | Feed de atividade escopado |

## 4. Serviços

- `NotificationService.notify(...)`: cria a notificação respeitando a preferência
  do canal `sistema` e publica `notification.created`. Integrado às conclusões de
  exportação (B6) e de relatório CSV — que também publicam `report.ready`.
- `ActivityService.record(...)`: alimenta o feed; já integrado ao cálculo de
  resultado (`resultado.calculado`) e à criação/edição de eventos de agenda.

## 5. Escopo de comunicação

Notificações são estritamente por `usuario_id`. Agenda e atividades usam o escopo
organizacional do ator (lotações ativas): `PortalScope.accessibleSchoolIds`,
`accessibleNucleoIds`, `isGlobalViewer` e `canViewNucleo` (novos). Ator com visão
de rede vê todos os escopos; demais veem apenas seu núcleo/escola ou onde participam.

## 6. Verificação

```powershell
cd backend
$env:DB_TEST_PORT = '3306'   # MariaDB local (ver memória db-testing-local)
php artisan migrate:fresh --env=testing --seed --force
php artisan test --filter=Notification
php artisan test --filter=Calendar
php artisan test            # 230 passed
php vendor/bin/pint --test  # passed
php artisan reverb:start    # validação manual de canais
```

## 7. Observações

- Em testes o broadcaster usa o driver `log` (não há servidor Reverb); a cobertura
  de eventos usa `Event::fake`/`assertDispatched` e a verificação dos canais.
- Eventos `ShouldDispatchAfterCommit` só publicam após o commit da transação.

## 8. Pendências encaminhadas

- **Painéis compostos por ator** (`dashboards/{ator}`, `.../kpis`,
  `.../desempenho`) e **`/alertas`** consolidam dados de B6/B7 e ficam para a
  fase de reconstrução visual (V2 web), reaproveitando estes endpoints.
- Entrega de notificações por **email/push** (a preferência já é modelada; o canal
  `sistema`/in-app está ativo).
