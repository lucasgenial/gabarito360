#!/usr/bin/env bash
# Entrada por fase: operador confere CI do SHA; backup final sempre obrigatório.
set -Eeuo pipefail
umask 077
[[ $(id -u) = 0 && $# = 3 ]] || { echo 'Uso (root): publicar-fase.sh SHA ATUAL FASE' >&2; exit 2; }
alvo=$1 anterior=$2 fase=$3
[[ $alvo =~ ^[0-9a-f]{40}$ && $anterior =~ ^[0-9a-f]{40}$ && $fase =~ ^[A-Za-z0-9][A-Za-z0-9._-]{0,63}$ ]] || exit 2
pasta="/root/g360-releases/$alvo"
[[ -f "$pasta/preparado-em" && $(cat "$pasta/anterior") = "$anterior" && $(cat "$pasta/fase") = "$fase" ]] || { echo 'Prepare e confira o pacote para o SHA atualmente publicado.' >&2; exit 1; }
[[ ! -e /root/g360-operacao/primeira-ativacao-pendente ]] || { echo 'Primeira ativação acumulada exige roteiro de vínculos e auditoria antes das publicações rotineiras.' >&2; exit 1; }
registro=$(mktemp "$pasta/publicacao-XXXXXX.log")
bash /root/g360-operacao/deploy.sh --commit "$alvo" --from "$anterior" --fase "$fase" --backup-final /root/g360-operacao/pausa-backup-final.sh 2>&1 | tee "$registro"
