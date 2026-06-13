# Runbooks Operacionais R7

## 1. Diagnostico inicial

```powershell
docker compose --env-file .env.docker ps
docker compose --env-file .env.docker logs --tail 200 nginx app queue reverb mariadb redis
Invoke-RestMethod http://127.0.0.1:8080/api/v1/health
```

Preserve `request_id`, horario UTC, ambiente e servico afetado. Nao inclua
senhas, tokens, imagens ou dados pessoais no chamado.

## 2. Aplicacao ou Nginx indisponivel

1. Confirmar estado de `nginx`, `app` e `migrate`.
2. Verificar health e logs.
3. Reiniciar somente o servico afetado.
4. Se uma migration falhou, interromper promocao e aplicar o plano de rollback.

```powershell
docker compose --env-file .env.docker restart app nginx
```

## 3. Fila parada ou OMR com falha

1. Verificar `queue`, Redis e `failed_jobs`.
2. Confirmar que a configuracao OMR existe dentro do container.
3. Nunca confirmar automaticamente leitura em falha.
4. Reiniciar worker; reprocessar apenas por fluxo autorizado e auditado.

```powershell
docker compose --env-file .env.docker logs --tail 200 queue
docker compose --env-file .env.docker exec app php artisan queue:failed
docker compose --env-file .env.docker restart queue
```

## 4. Reverb indisponivel

O snapshot HTTP continua sendo a fonte de recuperacao. Verifique origens
permitidas, Redis, segredo e logs. Nao publique a porta do Reverb.

```powershell
docker compose --env-file .env.docker logs --tail 200 reverb nginx
docker compose --env-file .env.docker restart reverb
```

## 5. MariaDB ou Redis indisponivel

1. Interromper operacoes de escrita e preservar evidencias.
2. Verificar volume, espaco, health e logs.
3. Nao recriar volume nem usar `down -v`.
4. Para perda de dados, executar o runbook de restauracao.

## 6. Escalonamento

| Severidade | Exemplo | Acao |
|---|---|---|
| S1 | perda, exposicao ou indisponibilidade total | interromper operacao, acionar Seguranca/Plataforma imediatamente |
| S2 | fila, Reverb ou modulo critico degradado | limitar fluxo afetado e corrigir antes de continuar |
| S3 | falha sem impacto em dados ou operacao principal | registrar, priorizar e acompanhar |

O piloto permanece bloqueado enquanto os gates fisicos registrados na
homologacao estiverem pendentes.
