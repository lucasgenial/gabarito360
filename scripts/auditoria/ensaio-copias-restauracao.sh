#!/usr/bin/env bash
# Segundo ciclo: snapshot já contém cópia parcial, staging e fila de execução.
set -euo pipefail
[[ "${GITHUB_ACTIONS:-}" == true && "${APP_ENV:-}" == testing && "${G360_AUDITORIA_ENSAIO:-}" == 1 ]]
[[ "${DB_DATABASE:-}" == g360_auditoria_ci && "${DB_HOST:-}" == 127.0.0.1 && "${REDIS_HOST:-}" == 127.0.0.1 ]]
[[ "${DB_PORT:-}" == 3307 && "${REDIS_PORT:-}" == 6380 ]]
[[ "${GITHUB_RUN_ID:-}" =~ ^[0-9]+$ && "${GITHUB_RUN_ATTEMPT:-}" =~ ^[0-9]+$ ]]
[[ -z "${DOCKER_HOST:-}" && "$(docker context show)" == default ]]
origem_db="au1-restauro-db-${GITHUB_RUN_ID}-${GITHUB_RUN_ATTEMPT}"
origem_redis="au1-restauro-redis-${GITHUB_RUN_ID}-${GITHUB_RUN_ATTEMPT}"
db="t5-b3-db-${GITHUB_RUN_ID}-${GITHUB_RUN_ATTEMPT}"
redis="t5-b3-redis-${GITHUB_RUN_ID}-${GITHUB_RUN_ATTEMPT}"
umask 077
pacote=$(mktemp -d /tmp/t5-b3-restauracao.XXXXXXXX)
criou_db=0; criou_redis=0
limpar() {
    if [[ "$criou_redis" == 1 ]]; then docker rm -fv "$redis" >/dev/null; fi
    if [[ "$criou_db" == 1 ]]; then docker rm -fv "$db" >/dev/null; fi
}
trap limpar EXIT
php scripts/auditoria/ensaio-copias-volume.php preparar
# Nenhum worker/produtor executa durante os dois snapshots.
docker exec -e MYSQL_PWD="$DB_PASSWORD" "$origem_db" mariadb-dump -uroot \
    --all-databases --single-transaction --routines --events --triggers --hex-blob > "$pacote/banco.sql"
docker exec "$origem_redis" redis-cli SAVE >/dev/null
docker cp "$origem_redis:/data/dump.rdb" "$pacote/dump.rdb"
(cd "$pacote" && sha256sum banco.sql dump.rdb > SHA256SUMS)
docker stop "$origem_db" "$origem_redis" >/dev/null
docker create --name "$db" -p 127.0.0.1:3308:3306 -e MARIADB_ROOT_PASSWORD="$DB_PASSWORD" mariadb:10.11 >/dev/null
criou_db=1
docker start "$db" >/dev/null
pronto=0
for tentativa in $(seq 1 60); do
    if docker exec "$db" healthcheck.sh --connect --innodb_initialized >/dev/null 2>&1; then pronto=1; break; fi
    sleep 1
done
[[ "$pronto" == 1 ]]
(cd "$pacote" && sha256sum --check SHA256SUMS >/dev/null)
docker exec -i -e MYSQL_PWD="$DB_PASSWORD" "$db" mariadb -uroot < "$pacote/banco.sql"
docker exec -e MYSQL_PWD="$DB_PASSWORD" "$db" mariadb -uroot -e 'FLUSH PRIVILEGES'
docker create --name "$redis" -p 127.0.0.1:6381:6379 redis:7-alpine >/dev/null
criou_redis=1
docker cp "$pacote/dump.rdb" "$redis:/data/dump.rdb"
docker start "$redis" >/dev/null
pronto=0
for tentativa in $(seq 1 20); do
    if docker exec "$redis" redis-cli ping >/dev/null 2>&1; then pronto=1; break; fi
    sleep 1
done
[[ "$pronto" == 1 ]]
export DB_PORT=3308 REDIS_PORT=6381
php scripts/auditoria/ensaio-copias-volume.php recuperar
