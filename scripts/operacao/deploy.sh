#!/usr/bin/env bash
# Executar no CT 111, como root, somente para um commit revisado e autorizado.
set -Eeuo pipefail
# Git/Composer devem gerar código legível pelo PHP, mesmo com umask restritivo do chamador.
# Temporários privados continuam protegidos por mktemp/chmod e pelo hook de backup.
umask 022

uso() {
  echo 'Uso: bash scripts/operacao/deploy.sh --commit SHA --from SHA [--check] [--manter-manutencao] [--backup-final SCRIPT] [--wait SEGUNDOS] [--root DIRETORIO]'
}

[[ "$(id -u)" = 0 ]] || { echo 'Execute como root no contêiner da aplicação.' >&2; exit 1; }

# O Git pode substituir este arquivo durante o deploy; executar uma cópia estável.
if [[ "${G360_DEPLOY_SNAPSHOT:-}" != 1 ]]; then
  copia=$(mktemp /tmp/g360-deploy.XXXXXX)
  cp -- "${BASH_SOURCE[0]}" "$copia"
  chmod 700 "$copia"
  trap 'rm -f -- "$copia"' EXIT
  if G360_DEPLOY_SNAPSHOT=1 bash "$copia" "$@"; then exit 0; else exit "$?"; fi
fi

RAIZ=/home/lucasdev/projetos/gabarito360
ALVO= ANTERIOR= CHECK=0 ESPERA=300 MANTER_MANUTENCAO=0 BACKUP_FINAL=
while (( $# )); do
  case "$1" in
    --commit|--from|--root|--wait|--backup-final)
      (( $# >= 2 )) || { uso >&2; exit 2; }
      case "$1" in --commit) ALVO=$2 ;; --from) ANTERIOR=$2 ;; --root) RAIZ=$2 ;; --wait) ESPERA=$2 ;; --backup-final) BACKUP_FINAL=$2 ;; esac
      shift 2 ;;
    --check) CHECK=1; shift ;;
    --manter-manutencao) MANTER_MANUTENCAO=1; shift ;;
    --help) uso; exit 0 ;;
    *) uso >&2; exit 2 ;;
  esac
done
[[ "$ALVO" =~ ^[0-9a-f]{40}$ && "$ANTERIOR" =~ ^[0-9a-f]{40}$ ]] || { echo 'Informe os dois SHAs completos em minúsculas.' >&2; exit 2; }
[[ "$ESPERA" =~ ^(0|[1-9][0-9]{0,3})$ && "$ESPERA" -le 3600 ]] || { echo 'Espera deve ser de 0 a 3600 segundos.' >&2; exit 2; }
RAIZ=$(realpath -e -- "$RAIZ")
[[ -d "$RAIZ/.git" && -d "$RAIZ/apps/api" && -d "$RAIZ/apps/web" ]] || { echo 'Raiz do projeto inválida.' >&2; exit 2; }
for comando in git php composer runuser systemctl curl flock; do
  command -v "$comando" >/dev/null || { echo "Ferramenta ausente: $comando" >&2; exit 1; }
done
if [[ -n "$BACKUP_FINAL" ]]; then
  BACKUP_FINAL=$(realpath -e -- "$BACKUP_FINAL")
  # Hook revisado, de root, sem escrita por grupo/outros; copiar antes do deploy.
  [[ -f "$BACKUP_FINAL" && "$(stat -c %u "$BACKUP_FINAL")" = 0 ]]
  modo=$(stat -c %a "$BACKUP_FINAL")
  (( (8#$modo & 0022) == 0 )) || { echo 'Hook de backup permite escrita indevida.' >&2; exit 2; }
  [[ "$BACKUP_FINAL" == /root/* ]] || { echo 'Instale o hook revisado dentro de /root.' >&2; exit 2; }
fi
exec 9>"$RAIZ/.git/g360-deploy.lock"
flock -n 9 || { echo 'Outro deploy está em andamento.' >&2; exit 1; }

etapa=preflight mudou=0 manutencao_api=0 manutencao_web=0 timer_parado=0 workers_parados=0
checkpoint_iniciado=0 fpm_parado=0
helper= cache_composer= hook_copia=
git_projeto() { runuser -u lucasdev -- git -C "$RAIZ" "$@"; }
artisan() { (cd "$RAIZ/apps/$1"; shift; runuser -u www-data -- php artisan "$@"); }
servicos_ativos() {
  local unidade
  for unidade in "$@"; do systemctl is-active --quiet "$unidade"; done
}
confirmar_parados() {
  local unidade estado
  for unidade in gabarito360-worker@1 gabarito360-worker@2 gabarito360-agendador.timer gabarito360-agendador.service; do
    estado=$(systemctl show "$unidade" --property=ActiveState --value)
    [[ "$estado" = inactive ]] || { log "Serviço não está parado: $unidade."; return 1; }
  done
}
log() { printf '[%s] %s\n' "$(date -u +%FT%TZ)" "$*"; }
limpar() {
  [[ -z "$hook_copia" ]] || rm -f -- "$hook_copia"
  [[ -z "$helper" ]] || rm -f -- "$helper"
  if [[ "$cache_composer" == /tmp/g360-composer.* && -d "$cache_composer" ]]; then
    rm -rf -- "$cache_composer"
  fi
}
falhou() {
  local codigo=$1
  trap - ERR INT TERM
  set +e
  log "FALHA na etapa $etapa. Anterior: $ANTERIOR. Alvo: $ALVO."
  if (( checkpoint_iniciado )); then
    artisan web down --retry=60
    artisan api down --retry=60
    systemctl stop gabarito360-agendador.timer gabarito360-agendador.service gabarito360-worker@1 gabarito360-worker@2 php8.3-fpm
    log 'Checkpoint iniciado: manutenção e escritores parados preservados. Não retomar automaticamente; conferir backup e estado antes de recuperar.'
  elif (( mudou == 0 )); then
    (( workers_parados == 0 )) || systemctl start gabarito360-worker@1 gabarito360-worker@2
    (( timer_parado == 0 )) || systemctl start gabarito360-agendador.timer
    (( manutencao_api == 0 )) || artisan api up
    (( manutencao_web == 0 )) || artisan web up
    log 'Código anterior preservado; recuperação do estado anterior solicitada. Confira serviços e manutenção.'
  else
    artisan web down --retry=60
    artisan api down --retry=60
    log 'Manutenção preservada. Confira código, migrações e serviços antes de recuperar; nenhuma tabela foi apagada.'
  fi
  exit "$codigo"
}
trap 'falhou "$?"' ERR
trap 'falhou 130' INT
trap 'falhou 143' TERM
trap limpar EXIT

if [[ -n "$BACKUP_FINAL" ]]; then
  hook_copia=$(mktemp /tmp/g360-backup-hook.XXXXXX)
  cp -- "$BACKUP_FINAL" "$hook_copia"
  chmod 700 "$hook_copia"
fi

etapa=preflight-git
[[ "$(git_projeto rev-parse --show-toplevel)" = "$RAIZ" ]]
[[ "$(git_projeto branch --show-current)" = main ]]
[[ "$(git_projeto rev-parse HEAD)" = "$ANTERIOR" ]]
git_projeto diff --quiet
git_projeto diff --cached --quiet
for app in api web; do
  etapa="preflight-$app"
  [[ ! -f "$RAIZ/apps/$app/storage/framework/down" ]]
  [[ -f "$RAIZ/apps/$app/composer.lock" && -f "$RAIZ/apps/$app/vendor/autoload.php" ]]
  etapa="preflight-$app-vendor"
  runuser -u lucasdev -- test -w "$RAIZ/apps/$app/vendor"
  for pasta in bootstrap/cache storage; do
    etapa="preflight-$app-$pasta"
    runuser -u www-data -- test -w "$RAIZ/apps/$app/$pasta"
  done
done
etapa=preflight-servicos
servicos_ativos nginx php8.3-fpm redis-server gabarito360-worker@1 gabarito360-worker@2 gabarito360-agendador.timer
etapa=preflight-alvo
git_projeto fetch origin main
git_projeto cat-file -e "$ALVO^{commit}"
git_projeto merge-base --is-ancestor "$ANTERIOR" "$ALVO"
git_projeto merge-base --is-ancestor "$ALVO" origin/main

etapa=preflight-configuracao
helper=$(mktemp /tmp/g360-deploy-filas.XXXXXX)
cat > "$helper" <<'PHP'
<?php
$raiz=$argv[1];
try {
require $raiz.'/vendor/autoload.php';
$app=require $raiz.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
if (config('app.maintenance.driver') !== 'file') {
    fwrite(STDERR,"Este roteiro requer manutenção por arquivo.\n"); exit(1);
}
if (($argv[2] ?? '') === '--validar-corte') {
    if (config('espacos.ativo', false) && !app(App\Services\RestringirEspacos::class)->esquemaConcluido()) {
        fwrite(STDERR,"T2 ativo com corte pendente. Manter manutenção; usar roteiro de corte aprovado.\n"); exit(1);
    }
    echo "0\n"; exit(0);
}
if (($argv[2] ?? '') === '--somente-manutencao') { echo "0\n"; exit(0); }
if (config('queue.default') !== 'redis') { fwrite(STDERR,"Este roteiro requer fila Redis.\n"); exit(1); }
$quantidade=0;
foreach (['alta','padrao','baixa'] as $fila) {
    $quantidade += Illuminate\Support\Facades\Queue::connection('redis')->size($fila);
}
echo $quantidade.PHP_EOL;
} catch (Throwable $erro) {
    fwrite(STDERR,"Não foi possível conferir fila/manutenção; interrompido antes de prosseguir.\n"); exit(1);
}
PHP
chmod 644 "$helper"
runuser -u www-data -- php "$helper" "$RAIZ/apps/web" --somente-manutencao >/dev/null
fila() { runuser -u www-data -- php "$helper" "$RAIZ/apps/api"; }
pendentes=$(fila)
[[ "$pendentes" =~ ^[0-9]+$ ]]
log "Preflight aprovado. Fila: $pendentes. Anterior: $ANTERIOR. Alvo: $ALVO."
if (( CHECK )); then log 'CHECK concluído: sem publicação, manutenção ou migração.'; exit 0; fi

etapa=manutencao
manutencao_web=1; artisan web down --retry=60
timer_parado=1; systemctl stop gabarito360-agendador.timer
if (( MANTER_MANUTENCAO )) || [[ -n "$BACKUP_FINAL" ]]; then systemctl stop gabarito360-agendador.service; fi
etapa=drenagem
limite=$((SECONDS + ESPERA))
while true; do
  pendentes=$(fila)
  [[ "$pendentes" =~ ^[0-9]+$ ]]
  (( pendentes > 0 )) || break
  if (( SECONDS >= limite )); then log "Fila não esvaziou em $ESPERA s; deploy cancelado antes de mudar código."; false; fi
  sleep 5
done
# Sem --force, os workers pausam na manutenção da API; só fechá-la após drenar.
manutencao_api=1; artisan api down --retry=60
sleep 3
pendentes=$(fila)
[[ "$pendentes" = 0 ]] || { log 'Novo job entrou durante a manutenção; cancelar sem atualizar código.'; false; }
workers_parados=1; systemctl stop gabarito360-worker@1 gabarito360-worker@2
if (( MANTER_MANUTENCAO )) || [[ -n "$BACKUP_FINAL" ]]; then confirmar_parados; fi
if [[ -n "$BACKUP_FINAL" ]]; then
  etapa=backup-final
  checkpoint_iniciado=1
  # Encerrar também requisições PHP em andamento antes de criar o ponto coerente.
  fpm_parado=1; systemctl stop php8.3-fpm
  [[ "$(systemctl show php8.3-fpm --property=ActiveState --value)" = inactive ]]
  [[ "$(fila)" = 0 ]]
  log 'BACKUP FINAL: escritores e PHP-FPM parados; código e esquema ainda anteriores. Aguardando hook.'
  bash "$hook_copia" "$RAIZ" "$ANTERIOR" "$ALVO"
  # Um hook não deve reabrir escritores nem alterar código/manutenção.
  confirmar_parados
  [[ "$(systemctl show php8.3-fpm --property=ActiveState --value)" = inactive ]]
  [[ -f "$RAIZ/apps/api/storage/framework/down" && -f "$RAIZ/apps/web/storage/framework/down" ]]
  [[ "$(fila)" = 0 ]]
  log 'BACKUP FINAL CONFIRMADO: prosseguindo para atualização e migração.'
fi
etapa=codigo
[[ "$(git_projeto rev-parse HEAD)" = "$ANTERIOR" ]]
git_projeto diff --quiet
git_projeto diff --cached --quiet
mudou=1
git_projeto merge --ff-only "$ALVO"
[[ "$(git_projeto rev-parse HEAD)" = "$ALVO" ]]
etapa=dependencias
cache_composer=$(mktemp -d /tmp/g360-composer.XXXXXX)
chown lucasdev:lucasdev "$cache_composer"
for app in api web; do
  # Vendor pertence ao usuário do código; caches e descoberta pertencem ao usuário PHP.
  artisan "$app" config:clear
  artisan "$app" clear-compiled
  (cd "$RAIZ/apps/$app"; runuser -u lucasdev -- env COMPOSER_HOME="$cache_composer" composer install --no-dev --no-scripts --prefer-dist --no-interaction --no-progress --optimize-autoloader)
  artisan "$app" package:discover --no-interaction
done
etapa=migracoes
artisan api migrate --force --no-interaction
etapa=caches
for app in api web; do
  artisan "$app" config:cache
  artisan "$app" route:cache
  artisan "$app" view:cache
done
if (( fpm_parado )); then
  etapa=retomada-fpm
  systemctl start php8.3-fpm
  servicos_ativos php8.3-fpm
  fpm_parado=0
fi
if (( MANTER_MANUTENCAO )); then
  etapa=entrega-em-manutencao
  [[ -f "$RAIZ/apps/api/storage/framework/down" && -f "$RAIZ/apps/web/storage/framework/down" ]]
  confirmar_parados
  log "PREPARADO EM MANUTENÇÃO: $ALVO. Sem abertura HTTP, workers ou agendador. Corte e retomada exigem roteiro autorizado."
  exit 0
fi
# A flag do release não autoriza abrir um banco que ainda esteja só expandido.
etapa=verificacao-corte
runuser -u www-data -- php "$helper" "$RAIZ/apps/api" --validar-corte >/dev/null
etapa=servicos
systemctl reload php8.3-fpm
systemctl start gabarito360-worker@1 gabarito360-worker@2
workers_parados=0
servicos_ativos nginx php8.3-fpm redis-server gabarito360-worker@1 gabarito360-worker@2
etapa=abertura
artisan api up; manutencao_api=0
artisan web up; manutencao_web=0
etapa=http
for caminho in / /login /api/v1/health; do
  codigo=$(curl --connect-timeout 5 --max-time 20 -sS -o /dev/null -w '%{http_code}' -H 'Host: gabarito360.com.br' "http://127.0.0.1$caminho")
  log "Local $caminho HTTP $codigo"
  [[ "$codigo" = 200 ]]
  codigo=$(curl --connect-timeout 5 --max-time 20 -sS -o /dev/null -w '%{http_code}' "https://gabarito360.com.br$caminho")
  log "Público $caminho HTTP $codigo"
  [[ "$codigo" = 200 ]]
done
etapa=agendador
systemctl start gabarito360-agendador.timer
timer_parado=0
servicos_ativos gabarito360-agendador.timer
log "DEPLOY CONCLUÍDO: $ALVO. HTTP local e público aprovados; valide o fluxo alterado."
