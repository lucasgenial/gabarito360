param(
    [string] $ComposeFile,
    [string] $EnvFile,
    [string] $Database = 'gabarito360'
)

. (Join-Path $PSScriptRoot 'common.ps1')

$ComposeFile = if ($ComposeFile) { $ComposeFile } else { $script:DefaultComposeFile }
$EnvFile = if ($EnvFile) { $EnvFile } else { $script:DefaultEnvFile }
$timestamp = Get-Date -Format 'yyyyMMddHHmmss'
$backupDirectory = Join-Path (Join-Path $script:LocalRoot 'backups') "verification-$timestamp"
$restoreDirectory = Join-Path (Join-Path $script:LocalRoot 'restore-verification') $timestamp
$restoreDatabase = "gabarito360_restore_$timestamp"

& (Join-Path $PSScriptRoot 'backup.ps1') `
    -ComposeFile $ComposeFile `
    -EnvFile $EnvFile `
    -Database $Database `
    -OutputDirectory $backupDirectory | Out-Null

& (Join-Path $PSScriptRoot 'restore.ps1') `
    -BackupDirectory $backupDirectory `
    -ComposeFile $ComposeFile `
    -EnvFile $EnvFile `
    -TargetDatabase $restoreDatabase `
    -TargetStorageDirectory $restoreDirectory

$sourceTables = Invoke-ComposeOutput $ComposeFile $EnvFile @(
    'exec', '-T', '-e', "VERIFY_DATABASE=$Database", 'mariadb', 'sh', '-lc',
    'mariadb --batch --skip-column-names --user=root --password="$MARIADB_ROOT_PASSWORD" "$VERIFY_DATABASE" -e "SHOW TABLES;"'
)
$restoreTables = Invoke-ComposeOutput $ComposeFile $EnvFile @(
    'exec', '-T', '-e', "VERIFY_DATABASE=$restoreDatabase", 'mariadb', 'sh', '-lc',
    'mariadb --batch --skip-column-names --user=root --password="$MARIADB_ROOT_PASSWORD" "$VERIFY_DATABASE" -e "SHOW TABLES;"'
)

if ($sourceTables.Count -eq 0) {
    throw 'O banco de origem nao possui tabelas para validar.'
}

$differences = Compare-Object ($sourceTables | Sort-Object) ($restoreTables | Sort-Object)

if ($differences.Count -gt 0) {
    throw "Restauracao divergente: origem=$($sourceTables.Count), destino=$($restoreTables.Count)."
}

$restoredPrivate = Join-Path $restoreDirectory 'private'

if (-not (Test-Path -LiteralPath $restoredPrivate -PathType Container)) {
    throw 'O diretorio privado nao foi restaurado.'
}

Write-Host "Restauracao isolada validada no banco $restoreDatabase com $($restoreTables.Count) tabelas."
Write-Host "Evidencias preservadas em $backupDirectory e $restoreDirectory."
