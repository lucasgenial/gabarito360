# Registro de Execução - B6 (Resultados, Dashboards e Relatórios)

## Status: Concluído

Passo **B6** do [`21-plano-backend.md`](21-plano-backend.md) implementado e verificado
(suíte completa verde: 212 testes / 1123 asserções). Cobre correção automática
(herdada de B5), dashboards, relatório de prova, exportações multi-formato,
snapshots de indicadores e comparativos.

---

## 1. Traceability e contexto mobile (middleware)

Trabalho transversal que precedeu o restante do B6:

1. **`X-Request-ID` na auditoria de bloqueio** (`EnsureUserIsActive`,
   `EnsureMobileDeviceIsActive`): o suporte cruza um erro do app React Native
   com o registro exato no banco de auditoria via ID único de requisição.
2. **Contexto (mobile vs web)** nos metadados de auditoria de usuário e
   carregamento otimizado de `dispositivoMobile` (evita query quando o
   relacionamento já vem carregado) em rotas de alta frequência.

## 2. Tabelas e models

Migration aditiva `2026_06_15_100000_create_b6_analytics_tables.php` (as demais
tabelas de B6 — `resultados`, `resultados_questoes`, `relatorios`, `arquivos` —
já existiam desde a fundação operacional):

| Tabela | Papel |
|---|---|
| `snapshots_indicadores` | Fotografia de KPIs por escopo (aplicação/prova/escola/núcleo) |
| `exportacoes` | Artefatos de exportação (csv/pdf/xlsx) de relatórios, autorizados e auditados |
| `comparativos` | Comparações persistidas (escolas de um núcleo, turmas de uma prova) |

Models: `SnapshotIndicador`, `Exportacao`, `Comparativo` (HasUuids, casts,
relacionamentos). Check constraints de escopo/formato/status aplicados via DDL.

## 3. Endpoints `/api/v2` entregues

| Método | Rota | Descrição |
|---|---|---|
| GET | `/resultados?aplicacao_id=&aluno_id=&prova_id=` | Lista resultados vigentes (filtro obrigatório, escopo) |
| GET | `/resultados/{resultado}` | Detalhe com respostas por questão |
| GET | `/dashboards/aplicacao/{aplicacao}` | Progresso + indicadores da aplicação |
| GET | `/dashboards/prova/{prova}` | Indicadores gerais + por turma |
| GET | `/dashboards/prova/{prova}/snapshot` | Gera/persiste e retorna snapshot de indicadores |
| GET | `/relatorios/prova/{prova}` (`?turma_id=`) | Relatório da prova: KPIs, acertos por tema, aproveitamento, resultado por aluno |
| POST | `/relatorios/prova/{prova}/exportar` | Solicita exportação `{formato: csv\|pdf\|xlsx}` (idempotente, auditada) |
| GET | `/exportacoes`, `/exportacoes/{id}`, `/exportacoes/{id}/download` | Lista/status/download de exportações (do próprio solicitante, auditado) |
| GET | `/comparativos/nucleo/{nucleo}` (`?prova_id=`) | Comparativo das escolas do núcleo |
| GET/POST | `/relatorios` … | (já existente) relatório CSV por aplicação |

## 4. Exportações multi-formato

- **CSV** nativo (com proteção contra CSV injection).
- **PDF** via `barryvdh/laravel-dompdf` (KPIs + acertos por tema + tabela de alunos).
- **XLSX** OOXML válido **sem depender da extensão `zip`**: escritor ZIP em PHP
  puro (método *store* + CRC32), em `app/Support/Export/` (`StoredZipWriter`,
  `XlsxWriter`). Decisão tomada porque o ambiente não tem a extensão `zip`.
- `ProvaReportService` é a fonte única dos números — tela e arquivo coincidem.
- Toda exportação é autorizada por escopo, registra `exportacao` + `arquivo` e
  audita `exportacao.requested` / `exportacao.downloaded`.

## 5. Correção do harness de teste (guard cache)

Os testes B6 fazem várias requisições autenticadas no mesmo método (ex.: confirmar
como aplicador e depois consultar como gestor). O guard `auth:sanctum`
(`RequestGuard`) memoriza o usuário resolvido na primeira requisição e a instância
sobrevive entre as chamadas HTTP de um mesmo teste — a segunda requisição
respondia como o primeiro usuário. **Não é bug de produção** (cada request real é
isolado). Adicionado `actingAsToken(User)` em `InteractsWithIdentity`
(`forgetGuards()` + Bearer token), usado nos testes com múltiplos atores.

## 6. Verificação

```powershell
cd backend
$env:DB_TEST_PORT = '3306'   # MariaDB local (ver memória db-testing-local)
php artisan migrate:fresh --env=testing --seed --force
php artisan test --filter=Result
php artisan test --filter=Report
php artisan test --filter=Export
php artisan test            # 212 passed
php vendor/bin/pint --test  # passed
```

## 7. Instruções para IAs (mantidas)

Ao trabalhar em qualquer Action de recepção de dados do aplicativo (ex.: envio de
gabarito):
1. Validar a chave de idempotência (`Idempotency-Key`).
2. Incluir o `dispositivo_id` na auditoria da operação.
3. Retornar erros em `UPPER_SNAKE_CASE` conforme padrão da API V2.

## 8. Pendências encaminhadas a outros passos

- **Dashboards por ator** (7 painéis: KPIs/desempenho/atividades) e **agenda/
  alertas** dependem de `atividades_recentes`/`eventos_agenda` — **B7**.
- **Idempotency-Key** já é honrado nas exportações; reforço de obrigatoriedade
  por endpoint segue o padrão transversal.
- **Hardware metadata** de câmera em `dispositivos_mobile` para diagnóstico OMR —
  avaliar em V2-08.
