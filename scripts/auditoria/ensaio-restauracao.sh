#!/usr/bin/env bash
# Somente CI: restaura banco completo e fila em contêineres/volumes novos.
set -euo pipefail
[[ "${GITHUB_ACTIONS:-}" == true && "${APP_ENV:-}" == testing && "${G360_AUDITORIA_ENSAIO:-}" == 1 ]]
[[ "${DB_DATABASE:-}" == g360_auditoria_ci && "${DB_HOST:-}" == 127.0.0.1 && "${REDIS_HOST:-}" == 127.0.0.1 ]]
[[ "${GITHUB_RUN_ID:-}" =~ ^[0-9]+$ && "${GITHUB_RUN_ATTEMPT:-}" =~ ^[0-9]+$ ]]
origem_db=$(docker ps -q --filter ancestor=mariadb:10.11)
origem_redis=$(docker ps -q --filter ancestor=redis:7-alpine)
[[ "$origem_db" =~ ^[a-f0-9]{12,64}$ && "$origem_redis" =~ ^[a-f0-9]{12,64}$ ]]
# Não aceitar um Docker remoto nem qualquer destino operacional.
[[ -z "${DOCKER_HOST:-}" && "$(docker context show)" == default ]]
db="au1-restauro-db-${GITHUB_RUN_ID}-${GITHUB_RUN_ATTEMPT}"
redis="au1-restauro-redis-${GITHUB_RUN_ID}-${GITHUB_RUN_ATTEMPT}"
umask 077
pacote=$(mktemp -d /tmp/au1-restauracao.XXXXXXXX)
criou_db=0; criou_redis=0
limpar() {
    if [[ "$criou_redis" == 1 ]]; then docker rm -fv "$redis" >/dev/null; fi
    if [[ "$criou_db" == 1 ]]; then docker rm -fv "$db" >/dev/null; fi
    # O pacote privado é descartado pelo runner; não subir como artefato nem imprimir seu conteúdo.
}
trap limpar EXIT
inicio=$SECONDS
php apps/api/artisan g360:conferir-auditoria > "$pacote/antes.json"
php apps/api/artisan g360:arquivar-auditoria 2026-01 "$pacote/mensal" > /dev/null
docker exec -e MYSQL_PWD="$DB_PASSWORD" "$origem_db" mariadb-dump -uroot \
    --all-databases --single-transaction --routines --events --triggers --hex-blob > "$pacote/banco.sql"
docker exec "$origem_redis" redis-cli SAVE > /dev/null
docker cp "$origem_redis:/data/dump.rdb" "$pacote/dump.rdb"
(cd "$pacote" && sha256sum banco.sql dump.rdb antes.json > SHA256SUMS)
# Origem fica parada e preservada. Não reutilizar seus contêineres nem volumes.
docker stop "$origem_db" "$origem_redis" > /dev/null
docker create --name "$db" -p 127.0.0.1:3307:3306 \
    -e MARIADB_ROOT_PASSWORD="$DB_PASSWORD" mariadb:10.11 > /dev/null
criou_db=1
docker start "$db" > /dev/null
pronto=0
for tentativa in $(seq 1 60); do
    if docker exec "$db" healthcheck.sh --connect --innodb_initialized >/dev/null 2>&1; then pronto=1; break; fi
    sleep 1
done
[[ "$pronto" == 1 ]]
(cd "$pacote" && sha256sum --check SHA256SUMS > /dev/null)
docker exec -i -e MYSQL_PWD="$DB_PASSWORD" "$db" mariadb -uroot < "$pacote/banco.sql"
docker exec -e MYSQL_PWD="$DB_PASSWORD" "$db" mariadb -uroot -e 'FLUSH PRIVILEGES'
docker create --name "$redis" -p 127.0.0.1:6380:6379 redis:7-alpine > /dev/null
criou_redis=1
docker cp "$pacote/dump.rdb" "$redis:/data/dump.rdb"
docker start "$redis" > /dev/null
pronto=0
for tentativa in $(seq 1 20); do
    if docker exec "$redis" redis-cli ping >/dev/null 2>&1; then pronto=1; break; fi
    sleep 1
done
[[ "$pronto" == 1 ]]
export DB_PORT=3307 REDIS_PORT=6380
# Mesma chave local do ensaio; nunca gerar outra chave entre backup e recuperação.
php apps/api/artisan g360:conferir-auditoria --comparar="$pacote/antes.json" > /dev/null
php apps/api/artisan g360:arquivar-auditoria 2026-01 "$pacote/mensal" --verificar > /dev/null
php scripts/auditoria/ensaio-fila.php recuperar
# A pendência restaurada é um evento tardio de janeiro: uma nova revisão tem dois registros.
php apps/api/artisan g360:arquivar-auditoria 2026-01 "$pacote/mensal-posterior" > /dev/null
php apps/api/artisan g360:arquivar-auditoria 2026-01 "$pacote/mensal" --verificar > /dev/null
printf '{"fase":"restauracao-servicos","containers_novos":true,"manifesto_confere":true,"arquivo_verificado":true,"duracao_segundos":%d}\n' "$((SECONDS-inicio))"
