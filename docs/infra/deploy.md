# Deploy do Gabarito360

## 1. Escopo

Este documento descreve a referencia implantavel criada no R7 para homologacao
em host unico. Producao deve acrescentar ingress/TLS gerenciado, cofre de
segredos, storage de objetos privado, monitoramento e politica de backup do
provedor.

O Compose nao autoriza piloto e nao substitui os gates fisicos de OMR,
dispositivos e LGPD organizacional.

## 2. Servicos

| Servico | Responsabilidade | Exposicao |
|---|---|---|
| `nginx` | unica entrada HTTP, arquivos estaticos e proxy do Reverb | porta configurada por `G360_HTTP_PORT` |
| `app` | PHP-FPM e aplicacao Laravel | somente rede Docker |
| `migrate` | migrations controladas antes da subida | tarefa transitoria |
| `queue` | filas Redis e processamento OMR | somente rede Docker |
| `scheduler` | agendamentos Laravel | somente rede Docker |
| `reverb` | WebSocket privado | somente via Nginx |
| `mariadb` | persistencia relacional | somente rede interna |
| `redis` | filas, cache, sessoes e escala do Reverb | somente rede interna |

MariaDB e Redis nao possuem `ports` no Compose. A aplicacao confia nos cabecalhos
do proxy somente no ambiente containerizado, onde PHP-FPM nao e publicado.

## 3. Preparacao

Requisitos:

- Docker Engine com Compose v2;
- PowerShell 7 para os scripts de continuidade;
- credenciais unicas para o ambiente;
- dominio e TLS antes de expor homologacao fora de rede controlada.

```powershell
Copy-Item .env.docker.example .env.docker
cd backend
php artisan key:generate --show
cd ..
```

Edite `.env.docker` e substitua `APP_KEY`, senhas de MariaDB/Redis, segredo do
Reverb, URL, host/porta/esquema WebSocket publicos e origens permitidas. O
arquivo e ignorado pelo Git.

## 4. Subida e verificacao

```powershell
docker compose --env-file .env.docker -f compose.yaml config --quiet
docker compose --env-file .env.docker -f compose.yaml build app nginx
docker compose --env-file .env.docker -f compose.yaml up -d --wait
docker compose --env-file .env.docker -f compose.yaml ps
Invoke-RestMethod http://127.0.0.1:8080/api/v1/health
```

O servico `migrate` executa `php artisan migrate --force` antes da aplicacao.
Seeders demonstrativos nao sao executados no deploy.

## 5. Operacao

```powershell
# Logs
docker compose --env-file .env.docker logs --tail 200 app queue reverb nginx

# Reinicio de um servico sem recriar banco
docker compose --env-file .env.docker restart queue

# Encerramento preservando volumes
docker compose --env-file .env.docker down
```

Nunca use `down -v` em ambiente com dados que devam ser preservados.

## 6. Atualizacao e rollback

1. Gerar e validar backup.
2. Publicar imagens identificadas por tag imutavel em `G360_IMAGE_TAG`.
3. Executar `docker compose up -d --wait`.
4. Validar health, login, fila, Reverb e uma operacao autorizada.
5. Em falha, restaurar a tag anterior. Restaurar banco somente quando a migration
   nao for retrocompativel e conforme o runbook.

O merge e o deploy de producao exigem revisao humana. Nao ha deploy automatico
de producao nesta etapa.

## 7. Seguranca minima

- `APP_DEBUG=false` fora do local.
- Segredos nao entram em imagem, Git, logs ou PR.
- TLS e obrigatorio fora da rede local.
- `REVERB_ALLOWED_ORIGINS` lista somente dominios autorizados.
- Banco, Redis, PHP-FPM, filas e Reverb nao sao publicados diretamente.
- Arquivos de negocio permanecem no volume privado; producao deve usar storage
  privado compatível com S3 e lifecycle aprovado.
- Backups devem ser criptografados fora do host e ter restauracao periodica.

## 8. Gate de promocao

Antes de promover:

```powershell
docker compose --env-file .env.docker config --quiet
powershell -ExecutionPolicy Bypass -File scripts/infra/verify-restore.ps1
```

Consulte o [relatorio de homologacao](../piloto/relatorio-homologacao-r7.md) e o
[plano de merge](../20-plano-merge-r7.md).
