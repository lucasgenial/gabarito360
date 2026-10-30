#!/bin/bash
# Cópia isolada de um branch em ~/g360-dev (usa vendor/.env/venv da produção) para validar antes do merge.
# Uso (como lucasdev): bash devcopy.sh <branch>
set -e
BR="$1"
P=~/projetos/gabarito360/apps
rm -rf ~/g360-dev
git clone -q --depth 1 --branch "$BR" git@github.com:lucasgenial/gabarito360.git ~/g360-dev
cd ~/g360-dev/apps
for a in api web; do
  cp "$P/$a/.env" "$a/.env"
  cp -r "$P/$a/vendor" "$a/"
  mkdir -p "$a/bootstrap/cache" "$a/storage/framework/views" "$a/storage/framework/cache" "$a/storage/framework/sessions" "$a/storage/logs"
  (cd "$a" && composer dump-autoload -q 2>&1 | tail -1)
done
ln -s "$P/api/omr/.venv" api/omr/.venv
true
for a in api web; do (cd "$a" && php artisan view:cache | grep -i cached); done
chmod -R o+rX ~/g360-dev
chmod -R 777 ~/g360-dev/apps/*/storage ~/g360-dev/apps/*/bootstrap/cache
echo "devcopy ok"
