param(
    [Parameter(Mandatory = $true)]
    [string] $BackupDirectory,
    [string] $ComposeFile,
    [string] $EnvFile,
    [string] $TargetDatabase = 'gabarito360_restore_verification',
    [string] $TargetStorageDirectory,
    [switch] $AllowDestructiveRestore
)

. (Join-Path $PSScriptRoot 'common.ps1')

$ComposeFile = if ($ComposeFile) { $ComposeFile } else { $script:DefaultComposeFile }
$EnvFile = if ($EnvFile) { $EnvFile } else { $script:DefaultEnvFile }
$TargetStorageDirectory = if ($TargetStorageDirectory) {
    $TargetStorageDirectory
} else {
    Join-Path (Join-Path $script:LocalRoot 'restore-verification') (Get-Date -Format 'yyyyMMdd-HHmmss')
}

Assert-DockerAvailable
Assert-ComposeInputs $ComposeFile $EnvFile
Assert-SafeDatabaseName $TargetDatabase
Assert-PathWithinLocal $BackupDirectory
Assert-PathWithinLocal $TargetStorageDirectory

if ((Test-Path -LiteralPath $TargetStorageDirectory) -and (Get-ChildItem -LiteralPath $TargetStorageDirectory -Force | Select-Object -First 1)) {
    throw "Diretorio de restauracao nao esta vazio: $TargetStorageDirectory"
}

$manifest = Get-Manifest $BackupDirectory
Assert-BackupChecksums $BackupDirectory $manifest

$isVerificationDatabase = $TargetDatabase -match '_restore(?:_|$)'

if (-not $isVerificationDatabase -and -not $AllowDestructiveRestore) {
    throw 'Restauracao recusada: use um banco isolado contendo _restore ou informe -AllowDestructiveRestore.'
}

$timestamp = Get-Date -Format 'yyyyMMdd-HHmmss'
$databaseTemp = "/tmp/gabarito360-restore-$timestamp.sql"
$databaseFile = Join-Path $BackupDirectory 'database.sql'
$storageFile = Join-Path $BackupDirectory 'private-storage.tar.gz'
$restoreCommand = 'mariadb --user=root --password="$MARIADB_ROOT_PASSWORD" -e "DROP DATABASE IF EXISTS \`$RESTORE_DATABASE\`; CREATE DATABASE \`$RESTORE_DATABASE\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" && mariadb --user=root --password="$MARIADB_ROOT_PASSWORD" "$RESTORE_DATABASE" < ' + $databaseTemp

try {
    Invoke-Compose $ComposeFile $EnvFile @('cp', $databaseFile, "mariadb:$databaseTemp")
    Invoke-Compose $ComposeFile $EnvFile @(
        'exec', '-T', '-e', "RESTORE_DATABASE=$TargetDatabase", 'mariadb', 'sh', '-lc',
        $restoreCommand
    )
} finally {
    Invoke-Compose $ComposeFile $EnvFile @('exec', '-T', 'mariadb', 'rm', '-f', $databaseTemp) -AllowFailure | Out-Null
}

New-Item -ItemType Directory -Force -Path $TargetStorageDirectory | Out-Null
& tar -xzf $storageFile -C $TargetStorageDirectory

if ($LASTEXITCODE -ne 0) {
    throw 'Falha ao restaurar o arquivo de storage.'
}

Write-Host "Banco restaurado em $TargetDatabase."
Write-Host "Storage restaurado isoladamente em $TargetStorageDirectory."
