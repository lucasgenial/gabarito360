#!/bin/bash
# Auditoria completa de um branch já enviado ao GitHub. Uso: bash scripts/auditoria/rodar.sh <branch>
# As telas têm dados reais de alunos: ficam numa pasta temporária, apagada ao final, e nunca entram no repositório.
set -e
BRANCH="$1"
[ -n "$BRANCH" ] || { echo "Uso: rodar.sh <branch>"; exit 2; }
AQUI="$(cd "$(dirname "$0")" && pwd)"
RAIZ="$(cd "$AQUI/../.." && pwd)"
SAIDA="$(mktemp -d)"
trap 'rm -rf "$SAIDA"' EXIT

ARQS="devcopy.sh aud_web.php aud_api.php aud_cfg.json remoto.sh"
(cd "$AQUI" && scp -q $ARQS pve:/tmp/)
ssh pve "for f in $ARQS; do pct push 111 /tmp/\$f /tmp/\$f --perms 644; rm -f /tmp/\$f; done
  pct exec 111 -- bash -c 'su lucasdev -c \"bash /tmp/devcopy.sh $BRANCH\" | tail -1; bash /tmp/remoto.sh'
  pct pull 111 /tmp/aud.tgz /tmp/aud.tgz; pct exec 111 -- rm -f /tmp/aud.tgz"
scp -q pve:/tmp/aud.tgz "$SAIDA/aud.tgz"
ssh pve "rm -f /tmp/aud.tgz"
tar xzf "$SAIDA/aud.tgz" -C "$SAIDA"

[ -d "$AQUI/node_modules" ] || (cd "$AQUI" && npm install --silent)
python "$RAIZ/scripts/verificar_telas.py" "$RAIZ/apps/web/resources/views"
(cd "$AQUI" && node medir.mjs "$SAIDA/aud" "$RAIZ")
