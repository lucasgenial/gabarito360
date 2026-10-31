# Deploy e serviços

Produção: CT 111 do Proxmox, em `/home/lucasdev/projetos/gabarito360`. Acesso por `ssh pve` e `pct exec 111`.

## Serviços

| Serviço | Papel |
|---|---|
| `nginx` | Entrada web |
| `php8.3-fpm` | Grupos `www` (web) e `api`, com 12 processos cada |
| `redis-server` | Cache, sessão e fila |
| `gabarito360-worker@1` e `@2` | Fila da API: `alta`, `padrao` e `baixa` |
| `gabarito360-agendador.timer` | Roda `schedule:run` a cada minuto |

Arquivos em `infra/systemd`. Ao mudá-los:

```bash
cp infra/systemd/gabarito360-* /etc/systemd/system/
systemctl daemon-reload
```

## Redis

`/etc/redis/redis.conf`:

```text
maxmemory 256mb
maxmemory-policy volatile-lru
appendonly yes
```

`volatile-lru` só expulsa chaves com validade, preservando a fila.

Valores no `.env`:

| Variável | API | Web |
|---|---|---|
| `CACHE_STORE` | `redis` | `redis` |
| `SESSION_DRIVER` | `redis` | `redis` |
| `QUEUE_CONNECTION` | `redis` | `redis` |
| `REDIS_PREFIX` | `g360_api_` | `g360_web_` |

A mudança de sessão desconecta quem estiver logado.

## Deploy

```bash
su lucasdev -c "git pull --ff-only"
```

Depois, como `www-data`, em `apps/api` e `apps/web`: `php artisan migrate --force`, quando houver migração, e `config:cache`, `route:cache` e `view:cache`. Recarregue com `systemctl reload php8.3-fpm` e reinicie os workers com `systemctl restart 'gabarito360-worker@*'`.

O pacote B5 troca estes passos por um script único.

## Falhas da fila

```bash
php artisan queue:failed
php artisan queue:retry all
```
