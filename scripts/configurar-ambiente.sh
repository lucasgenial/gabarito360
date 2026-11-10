#!/bin/sh
# Prepara um clone ou worktree do Gabarito360 para seguir as regras do projeto.
# Ativa os ganchos versionados em .githooks (ADR-0051: autoria sem IA).
set -e
raiz=$(git rev-parse --show-toplevel)
git -C "$raiz" config core.hooksPath .githooks
chmod +x "$raiz/.githooks/"* 2>/dev/null || true
echo "Ganchos ativados: core.hooksPath=.githooks"
