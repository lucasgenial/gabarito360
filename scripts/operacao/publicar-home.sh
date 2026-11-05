#!/usr/bin/env bash
# Instalar os quatro arquivos desta pasta no diretório operacional.
set -Eeuo pipefail
umask 022
[[ $(id -u) = 0 ]] || exit 2
raiz=$(realpath -e "${1:-/home/lucasdev/projetos/gabarito360}")
base=${2:-/var/www/gabarito360-publico}
public_url=${3:-https://gabarito360.com.br}
[[ $base = /* && $base != / && ! -L "$base" && -f "$raiz/apps/web/public/css/app.css" ]]
[[ ! -e "$raiz/apps/api/storage/framework/down" && ! -e "$raiz/apps/web/storage/framework/down" ]]
mkdir -p "$base/versoes" "$base/assets"
base=$(realpath -e "$base")
[[ ! -L "$base/versoes" && ! -L "$base/assets" ]]
[[ ! -e "$base/current" || -L "$base/current" ]]
[[ ! -L "$base/publicacao.lock" ]]
exec 9>"$base/publicacao.lock"
flock -n 9
versao=$(mktemp -d "$base/versoes/site-XXXXXXXX")
chmod 755 "$versao"
auxiliar=$(dirname "$(realpath "${BASH_SOURCE[0]}")")
temporario=$(mktemp -d /tmp/g360-export-home.XXXXXXXX)
chmod 755 "$temporario"
limpar() {
  rm -f -- "$temporario/exportar-home.php" "$temporario/assets-publicos.php" "$temporario/manutencao.html"
  rmdir -- "$temporario"
}
trap limpar EXIT
for arquivo in exportar-home.php assets-publicos.php manutencao.html; do
  [[ -f "$auxiliar/$arquivo" && ! -L "$auxiliar/$arquivo" ]]
  cp -- "$auxiliar/$arquivo" "$temporario/$arquivo"
  chmod 644 "$temporario/$arquivo"
done
php "$temporario/assets-publicos.php" exportar "$raiz/apps/web/public" "$versao" "$temporario/manutencao.html" "$public_url"
marca_sha=$(php "$temporario/assets-publicos.php" id "$raiz/apps/web/public")
php "$temporario/assets-publicos.php" verificar "$versao/marca-$marca_sha" "$versao/assets-manifest.json"
runuser -u www-data -- php "$temporario/exportar-home.php" "$raiz" "$public_url" > "$versao/index.html"
[[ -s "$versao/index.html" ]]
grep -Fq "/_institucional/marca-$marca_sha/css/app.css" "$versao/index.html"
grep -Fq "/_institucional/marca-$marca_sha/css/marca.css" "$versao/index.html"
chmod 644 "$versao/index.html"
css_sha=$(sha256sum "$versao/app.css" | cut -d' ' -f1)
# Endereços antigos continuam servindo o CSS congelado e jamais são removidos.
if [[ -e "$base/assets/$css_sha.css" ]]; then
  [[ ! -L "$base/assets/$css_sha.css" && $(sha256sum "$base/assets/$css_sha.css" | cut -d' ' -f1) = "$css_sha" ]]
else
  cp -- "$versao/app.css" "$base/assets/.$css_sha-$(basename "$versao").tmp"
  mv -T -- "$base/assets/.$css_sha-$(basename "$versao").tmp" "$base/assets/$css_sha.css"
fi
if [[ -e "$base/assets/marca-$marca_sha" || -L "$base/assets/marca-$marca_sha" ]]; then
  php "$temporario/assets-publicos.php" verificar "$base/assets/marca-$marca_sha" "$versao/assets-manifest.json"
else
  parcial="$base/assets/.marca-$marca_sha-$(basename "$versao").tmp"
  [[ ! -e "$parcial" && ! -L "$parcial" ]]
  cp -R -- "$versao/marca-$marca_sha" "$parcial"
  php "$temporario/assets-publicos.php" verificar "$parcial" "$versao/assets-manifest.json"
  mv -T -- "$parcial" "$base/assets/marca-$marca_sha"
fi
ln -s "$versao" "$base/proxima-$(basename "$versao")"
mv -Tf -- "$base/proxima-$(basename "$versao")" "$base/current"
echo 'Página inicial e marca publicadas independentemente; versões anteriores preservadas.'
