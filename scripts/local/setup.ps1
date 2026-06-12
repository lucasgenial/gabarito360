param(
    [switch] $Fresh
)

. (Join-Path $PSScriptRoot 'common.ps1')

Initialize-LocalDirectories

if (-not (Test-Path (Join-Path $script:MariaDbBin 'mariadbd.exe'))) {
    if (-not (Test-Path $script:MariaDbArchive)) {
        Write-Host "Baixando MariaDB $script:MariaDbVersion..."
        Invoke-WebRequest -Uri $script:MariaDbArchiveUrl -OutFile $script:MariaDbArchive -UseBasicParsing
    }

    $extractRoot = Join-Path $script:LocalRoot 'mariadb-extract'
    Assert-PathWithinLocal $extractRoot
    Assert-PathWithinLocal $script:MariaDbRoot
    Remove-Item $extractRoot -Recurse -Force -ErrorAction SilentlyContinue
    Expand-Archive -Path $script:MariaDbArchive -DestinationPath $extractRoot -Force
    $extracted = Get-ChildItem $extractRoot -Directory | Select-Object -First 1
    Move-Item $extracted.FullName $script:MariaDbRoot
    Remove-Item $extractRoot -Recurse -Force
}

if (-not (Test-Path (Join-Path $script:MariaDbData 'mysql'))) {
    $installer = Get-MariaDbExecutable 'mariadb-install-db'
    New-Item -ItemType Directory -Force -Path $script:MariaDbData | Out-Null
    & $installer "--datadir=$script:MariaDbData" '--password=' "--port=$script:MariaDbPort" --allow-remote-root-access

    if ($LASTEXITCODE -ne 0) {
        throw 'Nao foi possivel inicializar o MariaDB portatil.'
    }
}

@"
[mysqld]
basedir=$($script:MariaDbRoot -replace '\\','/')
datadir=$($script:MariaDbData -replace '\\','/')
port=$script:MariaDbPort
bind-address=$script:MariaDbHost
character-set-server=utf8mb4
collation-server=utf8mb4_unicode_ci
skip-name-resolve
"@ | Set-Content -Path $script:MariaDbConfig -Encoding ASCII

Start-PortableMariaDb
Ensure-Database 'gabarito360'
Ensure-Database 'gabarito360_testing'

$envPath = Join-Path $script:BackendRoot '.env'

if (-not (Test-Path $envPath)) {
    Copy-Item (Join-Path $script:BackendRoot '.env.example') $envPath
}

Set-EnvValue $envPath 'APP_NAME' '"Gabarito360"'
Set-EnvValue $envPath 'APP_ENV' 'local'
Set-EnvValue $envPath 'APP_DEBUG' 'true'
Set-EnvValue $envPath 'APP_URL' "http://${script:MariaDbHost}:$script:AppPort"
Set-EnvValue $envPath 'APP_LOCALE' 'pt_BR'
Set-EnvValue $envPath 'APP_FALLBACK_LOCALE' 'pt_BR'
Set-EnvValue $envPath 'APP_FAKER_LOCALE' 'pt_BR'
Set-EnvValue $envPath 'DB_CONNECTION' 'mariadb'
Set-EnvValue $envPath 'DB_HOST' $script:MariaDbHost
Set-EnvValue $envPath 'DB_PORT' $script:MariaDbPort
Set-EnvValue $envPath 'DB_DATABASE' 'gabarito360'
Set-EnvValue $envPath 'DB_USERNAME' $script:MariaDbUser
Set-EnvValue $envPath 'DB_PASSWORD' ''
Set-EnvValue $envPath 'SESSION_DRIVER' 'database'
Set-EnvValue $envPath 'CACHE_STORE' 'array'
Set-EnvValue $envPath 'QUEUE_CONNECTION' 'sync'

$php = Get-PhpExecutable
Push-Location $script:BackendRoot

try {
    & $php artisan config:clear

    if ($Fresh) {
        & $php artisan migrate:fresh --seed --force
    } else {
        & $php artisan migrate --seed --force
    }

    if ($LASTEXITCODE -ne 0) {
        throw 'As migrations do backend falharam.'
    }

    & $php artisan db:seed --class=LocalDemoSeeder --force

    if ($LASTEXITCODE -ne 0) {
        throw 'O seeder local de demonstracao falhou.'
    }
} finally {
    Pop-Location
}

Write-Host ''
Write-Host 'Ambiente local MariaDB configurado.'
Write-Host 'Execute: powershell -ExecutionPolicy Bypass -File scripts/local/start.ps1'
