#!/usr/bin/env bash
# Instalar junto de exportar-home.php e manutencao.html em /root/g360-operacao.
set -Eeuo pipefail
umask 022
[[ $(id -u) = 0 ]] || exit 2
raiz=$(realpath -e "${1:-/home/lucasdev/projetos/gabarito360}")
base=${2:-/var/www/gabarito360-publico}
[[ $base = /* && -f "$raiz/apps/web/public/css/app.css" ]]
[[ ! -e "$raiz/apps/api/storage/framework/down" && ! -e "$raiz/apps/web/storage/framework/down" ]]
mkdir -p "$base/versoes" "$base/assets"
[[ ! -e "$base/current" || -L "$base/current" ]]
exec 9>"$base/publicacao.lock"
flock -n 9
versao=$(mktemp -d "$base/versoes/site-XXXXXXXX")
chmod 755 "$versao"
auxiliar=$(dirname "$(realpath "${BASH_SOURCE[0]}")")
temporario=$(mktemp /tmp/g360-export-home.XXXXXX.php)
trap 'rm -f -- "$temporario"' EXIT
cp "$auxiliar/exportar-home.php" "$temporario"
chmod 644 "$temporario"
runuser -u www-data -- php "$temporario" "$raiz" > "$versao/index.html"
[[ -s "$versao/index.html" ]]
cp "$raiz/apps/web/public/css/app.css" "$versao/app.css"
css_sha=$(sha256sum "$versao/app.css" | cut -d' ' -f1)
grep -Fq "/_institucional/$css_sha.css" "$versao/index.html"
cp "$auxiliar/manutencao.html" "$versao/manutencao.html"
chmod 644 "$versao/"*
if [[ -e "$base/assets/$css_sha.css" ]]; then
  [[ $(sha256sum "$base/assets/$css_sha.css" | cut -d' ' -f1) = "$css_sha" ]]
else
  cp "$versao/app.css" "$base/assets/.$css_sha.tmp"
  mv -T "$base/assets/.$css_sha.tmp" "$base/assets/$css_sha.css"
fi
ln -s "$versao" "$base/proxima-$(basename "$versao")"
mv -Tf "$base/proxima-$(basename "$versao")" "$base/current"
echo 'Página inicial publicada de forma independente; versões anteriores preservadas.'
