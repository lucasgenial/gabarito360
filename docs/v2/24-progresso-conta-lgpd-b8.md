# Registro de Execução - B8 (Conta e LGPD)

## Status: Concluído

Passo **B8** do [`21-plano-backend.md`](21-plano-backend.md) implementado e
verificado (suíte completa verde: 244 testes / 1256 asserções). Entrega plano/uso,
solicitações LGPD rastreáveis (com anonimização/descarte) e consulta de auditoria.

---

## 1. Tabelas e models

Migration aditiva `2026_06_15_120000_create_account_lgpd_tables.php` (as demais
tabelas de B8 — `preferencias_usuario`, `solicitacoes_lgpd`, `consentimentos`,
`auditorias` — já existiam desde as fundações):

| Tabela | Papel |
|---|---|
| `planos_uso` | Plano contratado, limites e ciclo por núcleo |
| `politicas_retencao` | Prazos e base legal de retenção por tipo de dado |
| `execucoes_descarte` | Trilha de anonimização/inativação/descarte executados |

Também adiciona `titular_id` a `solicitacoes_lgpd` (o hash do titular permanece
para buscas anônimas) para tornar o processamento rastreável. Models:
`PlanoUso`, `PoliticaRetencao`, `ExecucaoDescarte`.

## 2. Recursos API

| Método | Rota | Descrição |
|---|---|---|
| GET | `/plano-uso` (`?nucleo_id=`) | Plano, limites e **uso real** (escolas/alunos/provas) do núcleo |
| GET | `/solicitacoes-lgpd` (`?status=&tipo=`) | Solicitante vê as suas; admin de configurações vê todas |
| POST | `/solicitacoes-lgpd` | Titular abre solicitação (acesso/correção/portabilidade/anonimização/exclusão); `aluno_id` opcional exige gestão de alunos |
| GET | `/solicitacoes-lgpd/{id}` | Detalhe (dono ou admin) com execuções de descarte |
| POST | `/solicitacoes-lgpd/{id}/processar` | Admin decide e executa (anonimização/inativação) |
| GET | `/auditorias` (`?acao=&entidade_tipo=&usuario_id=&de=&ate=`) | Trilha escopada |

Preferências da conta (aparência, idioma, acessibilidade) já são atendidas por
`GET/PATCH /me/preferencias` (B1).

## 3. Zona de perigo e LGPD (regra central)

"Excluir dados" **nunca** é exclusão física. A zona de perigo da tela de
configurações cria uma **solicitação LGPD rastreável**; o processamento por um
admin (`MANAGE_SETTINGS`) executa, via `ProcessLgpdRequestAction`:

- **exclusão/anonimização**: anonimiza o titular (usuário ou aluno) — nome,
  e-mail/documento/telefone, foto, etc. —, inativa o registro, revoga tokens (no
  caso de usuário) e grava `execucoes_descarte` (`acao = anonimizacao`);
- **acesso/correção/portabilidade**: conclui a solicitação com a decisão, sem
  descarte.

Tudo auditado (`lgpd.solicitacao.created`, `lgpd.solicitacao.processed`,
`lgpd.titular.anonimizado`). Reprocessar solicitação concluída → `422`.

## 4. Escopo e autorização

- **Plano/uso**: núcleo das lotações do ator; admin de rede informa `?nucleo_id=`
  (autorizado por `PortalScope.canViewNucleo`).
- **Solicitações LGPD**: qualquer autenticado abre sobre a própria conta;
  `aluno_id` exige `MANAGE_CLASSES_STUDENTS`; processar exige `MANAGE_SETTINGS`.
- **Auditorias**: admin (`MANAGE_SETTINGS`) vê tudo; gestor
  (`MANAGE_USERS_PROFILES_LINKS`) vê o próprio escopo (núcleo/escola) e as próprias
  ações; demais recebem `403`.

## 5. Verificação

```powershell
cd backend
$env:DB_TEST_PORT = '3306'   # MariaDB local (ver memória db-testing-local)
php artisan migrate:fresh --env=testing --seed --force
php artisan test --filter=Account
php artisan test --filter=Lgpd
php artisan test            # 244 passed
php vendor/bin/pint --test  # passed
```

## 6. Pendências encaminhadas

- **Políticas de retenção automáticas** (jobs que aplicam `politicas_retencao` e
  geram `execucoes_descarte` por expiração) — a modelagem está pronta; o
  agendamento entra no hardening (B9).
- **Consentimentos**: já gravados no onboarding (B1) e modelados; a gestão
  granular na tela de privacidade pode ganhar endpoint dedicado conforme a
  reconstrução visual exigir.
- **Importações** na tela de configurações reaproveitam o recurso de B3.
