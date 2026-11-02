#!/usr/bin/env bash
# CT 111: prepara pacote privado e diagnóstico; não publica nem migra.
set -Eeuo pipefail
umask 077
[[ $(id -u) = 0 && $# = 2 ]] || { echo 'Uso (root): preparar-release.sh SHA FASE' >&2; exit 2; }
alvo=$1 fase=$2
[[ $alvo =~ ^[0-9a-f]{40}$ && $fase =~ ^[A-Za-z0-9][A-Za-z0-9._-]{0,63}$ ]] || exit 2
raiz=/home/lucasdev/projetos/gabarito360
base=/root/g360-releases
mkdir -p "$base"
exec 9>"$base/preparacao.lock"
flock -n 9 || { echo 'Outra preparação em andamento.' >&2; exit 1; }
gitp() { runuser -u lucasdev -- git -C "$raiz" "$@"; }
gitp fetch origin main
gitp merge-base --is-ancestor "$alvo" origin/main
destino="$base/$alvo"
[[ ! -e "$destino" ]] || { echo "Pacote já existe: $destino; preserve sua evidência e confira antes de reutilizar." >&2; exit 1; }
mkdir "$destino"
gitp archive --format=tar "$alvo" > "$destino/codigo.tar"
sha256sum "$destino/codigo.tar" > "$destino/codigo.tar.sha256"
mkdir "$destino/codigo"
tar -xf "$destino/codigo.tar" -C "$destino/codigo"
printf '%s\n' "$fase" > "$destino/fase"
gitp rev-parse HEAD > "$destino/anterior"
# Não despejar diffs ou env no terminal; conservar exceções operacionais em área privada.
gitp diff --binary > "$destino/alteracoes-locais.patch"
gitp status --short > "$destino/git-status.txt"
php "$(dirname "${BASH_SOURCE[0]}")/conferir-release.php" "$raiz" "$destino/codigo" > "$destino/conferencia.json"
for servico in nginx php8.3-fpm redis-server gabarito360-worker@1 gabarito360-worker@2 gabarito360-agendador.timer; do
  systemctl is-active "$servico" >> "$destino/servicos.txt"
done
date -u +%FT%TZ > "$destino/preparado-em"
echo "PREPARADO: $destino. Código publicado, banco e flags preservados."
cat "$destino/conferencia.json"
