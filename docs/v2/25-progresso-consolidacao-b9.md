# Registro de Execução - B9 (Consolidação e Hardening)

## Status: Concluído — backend V2 (B0–B9) completo

Passo **B9** do [`21-plano-backend.md`](21-plano-backend.md) implementado e
verificado. Fecha a API v2: contrato OpenAPI completo e testado contra as rotas
reais, base limpa recriável e suíte verde.

---

## 1. Teste de contrato OpenAPI ↔ rotas

Novo `tests/Feature/Api/V2/Contract/OpenApiContractTest.php` parseia
`docs/openapi-v2.yaml` (via `symfony/yaml`) e exige **paridade** com as rotas
`/api/v2` reais, normalizando nomes de parâmetros (`{escola}` ≡ `{id}` ≡ `{}`):

- toda rota real precisa estar documentada;
- todo path documentado precisa existir como rota.

Isso trava a sincronia contrato↔implementação no CI (sem dependência externa de
validação de schema).

## 2. Reconciliação do openapi-v2.yaml

O contrato (escrito no planejamento) foi alinhado à implementação real de B6–B8:

**Adicionados** (estavam implementados, faltava documentar): `/health`,
`GET /me/foto`, `dashboards/aplicacao/{}`, `dashboards/prova/{}`,
`dashboards/prova/{}/snapshot`, `resultados` (lista), `relatorios`
(lista/criar/{}/download), `relatorios/prova/{}/exportar` (POST),
`comparativos/nucleo/{}`, agenda completa (`POST`, `{evento}` GET/PUT,
`/confirmar`), notificações completas (`/ler-todas`, `/preferencias` GET/PUT,
`/{}/ler`), `exportacoes/{}` + `/download`, `solicitacoes-lgpd/{}` +
`/processar`, `auditorias`.

**Removidos/ajustados** (planejados, não implementados como tal):
`dashboards/{ator}` (→ específicos), `alertas`, `resultados/{}.pdf`,
`relatorios/prova/{}/exportar/{formato}` (→ POST `/exportar`), `importacoes`,
schema órfão `Dashboard`.

## 3. Verificações de hardening

```powershell
cd backend
$env:DB_TEST_PORT = '3306'           # MariaDB local (ver memória db-testing-local)
composer validate --strict            # ./composer.json is valid
php vendor/bin/pint --test            # passed
php artisan migrate:fresh --env=testing --seed --force   # recria do zero
php artisan test                      # 245 passed (1257 asserções)
php artisan route:list                # nenhuma rota api/v1
npx --yes @redocly/cli@2.32.0 lint ../docs/openapi-v2.yaml  # valid 🎉 (2 warnings)
```

> Os 2 warnings do redocly são `operation-4xx-response` (recomendação de estilo
> em `/health` e em downloads binários); não são erros — o spec é válido.

## 4. Gate de conclusão do backend V2

| # | Critério | Estado |
|---|---|---|
| 1 | Toda capacidade do mockup tem fonte de dados real e autorizada | ✓ (B1–B8) |
| 2 | API expõe **apenas** `/api/v2`, com OpenAPI e teste de contrato | ✓ |
| 3 | Unicidades/índices de `07-modelagem` num esquema único V2 | ✓ |
| 4 | Idempotência, auditoria e escopo em captura/confirmação/importação/exportação | ✓ |
| 5 | `migrate:fresh --seed` + suíte completa em verde | ✓ (245) |
| 6 | Nenhum artefato `/api/v1`, página, app ou migration legada | ✓ |
| 7 | Matriz de rastreabilidade liga cada tabela/endpoint a uma tela | ✓ |

## 5. Próximo passo

O backend V2 está pronto para sustentar a **reconstrução visual** (web fiel ao
mockup) e o **app React Native** (V2-07+), ambos consumindo `/api/v2` conforme o
contrato OpenAPI agora versionado e testado. Itens encaminhados das fases
anteriores (painéis compostos por ator + `/alertas` em B7; retenção automática
via jobs em B8) entram na evolução pós-lançamento, reaproveitando os recursos já
entregues.
