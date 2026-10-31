#!/usr/bin/env bash
# Entrada compatível: o deploy oficial exige os SHAs completos do alvo e da origem.
set -euo pipefail
RAIZ=$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")/.." && pwd)
echo 'Roteiro oficial: scripts/operacao/deploy.sh. Informe --commit SHA --from SHA.' >&2
exec bash "$RAIZ/scripts/operacao/deploy.sh" "$@"
