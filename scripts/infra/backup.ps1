param(
    [string] $ComposeFile,
    [string] $EnvFile,
    [string] $Database = 'gabarito360',
    [string] $OutputDirectory
)

. (Join-Path $PSScriptRoot 'common.ps1')

$ComposeFile = if ($ComposeFile) { $ComposeFile } else { $script:DefaultComposeFile }
$EnvFile = if ($EnvFile) { $EnvFile } else { $script:DefaultEnvFile }
$timestamp = Get-Date -Format 'yyyyMMdd-HHmmss'
$OutputDirectory = if ($OutputDirectory) {
    $OutputDirectory
} else {
    Join-Path (Join-Path $script:LocalRoot 'backups') $timestamp
}

Assert-DockerAvailable
Assert-ComposeInputs $ComposeFile $EnvFile
Assert-SafeDatabaseName $Database
Assert-PathWithinLocal $OutputDirectory

if ((Test-Path -LiteralPath $OutputDirectory) -and (Get-ChildItem -LiteralPath $OutputDirectory -Force | Select-Object -First 1)) {
    throw "Diretorio de backup nao esta vazio: $OutputDirectory"
}

New-Item -ItemType Directory -Force -Path $OutputDirectory | Out-Null

$databaseFile = Join-Path $OutputDirectory 'database.sql'
$storageFile = Join-Path $OutputDirectory 'private-storage.tar.gz'
$databaseTemp = "/tmp/gabarito360-backup-$timestamp.sql"
$storageTemp = "/tmp/gabarito360-storage-$timestamp.tar.gz"

try {
    Invoke-Compose $ComposeFile $EnvFile @(
        'exec', '-T', '-e', "BACKUP_DATABASE=$Database", 'mariadb', 'sh', '-lc',
        "mariadb-dump --single-transaction --routines --triggers --events --user=root --password=`"`$MARIADB_ROOT_PASSWORD`" `"`$BACKUP_DATABASE`" > $databaseTemp"
    )
    Invoke-Compose $ComposeFile $EnvFile @('cp', "mariadb:$databaseTemp", $databaseFile)

    Invoke-Compose $ComposeFile $EnvFile @(
        'exec', '-T', 'app', 'tar', '-czf', $storageTemp, '-C', '/var/www/html/storage/app', 'private'
    )
    Invoke-Compose $ComposeFile $EnvFile @('cp', "app:$storageTemp", $storageFile)
} finally {
    Invoke-Compose $ComposeFile $EnvFile @('exec', '-T', 'mariadb', 'rm', '-f', $databaseTemp) -AllowFailure | Out-Null
    Invoke-Compose $ComposeFile $EnvFile @('exec', '-T', 'app', 'rm', '-f', $storageTemp) -AllowFailure | Out-Null
}

$files = [ordered] @{
    'database.sql' = (Get-FileHash -LiteralPath $databaseFile -Algorithm SHA256).Hash.ToLowerInvariant()
    'private-storage.tar.gz' = (Get-FileHash -LiteralPath $storageFile -Algorithm SHA256).Hash.ToLowerInvariant()
}
$manifest = [ordered] @{
    format_version = 1
    created_at_utc = (Get-Date).ToUniversalTime().ToString('o')
    source_database = $Database
    files = $files
}

$manifest | ConvertTo-Json -Depth 4 | Set-Content -LiteralPath (Join-Path $OutputDirectory 'manifest.json') -Encoding UTF8

Write-Host "Backup criado e verificado em $OutputDirectory"
Write-Output $OutputDirectory
