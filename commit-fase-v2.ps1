# commit-fase-v2.ps1
# Commits da fase V2 (planejamento sem legado + V2-01 mapa de telas).
# Uso (PowerShell, na raiz do repositorio):
#   cd C:\Users\Lucas Matos\projetos\gabarito360
#   powershell -ExecutionPolicy Bypass -File .\commit-fase-v2.ps1
#
# O script para na primeira falha. Push e tag ficam comentados no fim:
# revise os commits com 'git log --oneline' antes de publicar.

$ErrorActionPreference = "Stop"

# Garante que estamos na branch da V2
git checkout v2/mockup-canonico

# 1) React Native (decisao + contrato + flutter substituido)
git add docs/decisoes/ADR-D015-mobile-react-native.md `
        docs/v2/10-android-react-native.md docs/v2/10-android-flutter.md
git commit -m "docs(v2): adotar react native no mobile (ADR-D015)"

# 2) V2 sem legado
git add docs/decisoes/ADR-D016-v2-sem-legado.md
git commit -m "docs(v2): v2 como produto unico sem legado (ADR-D016)"

# 3) Analises de gap e reconstrucao/remocao
git add docs/v2/17-analise-gap.md docs/v2/18-analise-reaproveitamento.md
git commit -m "docs(v2): gap e plano de reconstrucao sem legado"

# 4) Plano de backend e estrategia de git
git add docs/v2/21-plano-backend.md docs/v2/19-estrategia-git.md
git commit -m "docs(v2): plano de backend limpo e estrategia de git"

# 5) Propagar 'sem legado' e react native nos canonicos
git add README.md AGENTS.md docs/v2/README.md `
        docs/v2/06-arquitetura-e-reaproveitamento-v1.md `
        docs/v2/07-modelagem-dados-mariadb.md `
        docs/v2/08-api-e-integracoes.md `
        docs/v2/16-plano-executavel-v2.md
git commit -m "docs(v2): refletir produto unico sem legado e react native"

# 6) Relatorio de execucao
git add docs/v2/20-relatorio-execucao.md
git commit -m "docs(v2): relatorio de execucao da fase de planejamento"

# 7) V2-01 - mapa tela a tela (30/30)
git add docs/v2/telas/
git commit -m "docs(v2): mapa executavel das 30 telas (V2-01)"

# 8) Este proprio script
git add commit-fase-v2.ps1
git commit -m "chore(v2): script de commits da fase de planejamento"

Write-Host ""
Write-Host "Commits criados. Revise com: git log --oneline -10" -ForegroundColor Green
Write-Host "Para publicar, rode manualmente:" -ForegroundColor Yellow
Write-Host "  git push -u origin v2/mockup-canonico"
Write-Host "  git tag -a v2.0.0-alpha.1 -m `"V2 canonizada: documentacao, planejamento e mapa de telas`""
Write-Host "  git push origin v2.0.0-alpha.1"
