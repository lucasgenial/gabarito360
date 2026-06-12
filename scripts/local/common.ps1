$ErrorActionPreference = 'Stop'

$script:RepoRoot = [System.IO.Path]::GetFullPath((Join-Path $PSScriptRoot '..\..'))
$script:BackendRoot = Join-Path $script:RepoRoot 'backend'
$script:LocalRoot = Join-Path $script:RepoRoot '.local'
$script:MariaDbVersion = '11.4.8'
$script:MariaDbArchiveUrl = "https://archive.mariadb.org/mariadb-$($script:MariaDbVersion)/winx64-packages/mariadb-$($script:MariaDbVersion)-winx64.zip"
$script:MariaDbArchive = Join-Path $script:LocalRoot "downloads\mariadb-$($script:MariaDbVersion)-winx64.zip"
$script:MariaDbRoot = Join-Path $script:LocalRoot 'mariadb'
$script:MariaDbBin = Join-Path $script:MariaDbRoot 'bin'
$script:MariaDbData = Join-Path $script:LocalRoot 'data\mariadb'
$script:MariaDbConfig = Join-Path $script:LocalRoot 'mariadb.ini'
$script:LogRoot = Join-Path $script:LocalRoot 'logs'
$script:RunRoot = Join-Path $script:LocalRoot 'run'
$script:MariaDbLog = Join-Path $script:LogRoot 'mariadb.log'
$script:MariaDbErrorLog = Join-Path $script:LogRoot 'mariadb-error.log'
$script:MariaDbPidFile = Join-Path $script:RunRoot 'mariadb.pid'
$script:LaravelLog = Join-Path $script:LogRoot 'laravel.log'
$script:LaravelErrorLog = Join-Path $script:LogRoot 'laravel-error.log'
$script:LaravelPidFile = Join-Path $script:RunRoot 'laravel.pid'
$script:LaravelServerPidFile = Join-Path $script:RunRoot 'laravel-server.pid'
$script:MariaDbHost = '127.0.0.1'
$script:MariaDbPort = 3307
$script:MariaDbUser = 'root'
$script:AppPort = 8000

function Initialize-LocalDirectories {
    @(
        $script:LocalRoot,
        $script:LogRoot,
        $script:RunRoot,
        (Split-Path $script:MariaDbArchive)
    ) | ForEach-Object {
        New-Item -ItemType Directory -Force -Path $_ | Out-Null
    }
}

function Normalize-ProcessPathVariable {
    $pathNames = @(
        [Environment]::GetEnvironmentVariables('Process').Keys |
            Where-Object { $_ -ieq 'Path' }
    )

    if ($pathNames.Count -le 1) {
        return
    }

    $pathValue = $env:Path

    $pathNames | ForEach-Object {
        [Environment]::SetEnvironmentVariable([string] $_, $null, 'Process')
    }

    [Environment]::SetEnvironmentVariable('Path', $pathValue, 'Process')
}

function Assert-PathWithinLocal([string] $Path) {
    $resolvedLocal = [System.IO.Path]::GetFullPath($script:LocalRoot).TrimEnd('\') + '\'
    $resolvedPath = [System.IO.Path]::GetFullPath($Path)

    if (-not $resolvedPath.StartsWith($resolvedLocal, [System.StringComparison]::OrdinalIgnoreCase)) {
        throw "Operacao recusada fora de .local: $resolvedPath"
    }
}

function Get-PhpExecutable {
    $php = Get-Command php -ErrorAction SilentlyContinue

    if ($null -ne $php) {
        return $php.Source
    }

    $xamppPhp = 'C:\xampp\php\php.exe'

    if (Test-Path $xamppPhp) {
        return $xamppPhp
    }

    throw 'PHP nao encontrado. Instale o PHP 8.3+ ou disponibilize php.exe no PATH.'
}

function Get-MariaDbExecutable([string] $Name) {
    $executable = Join-Path $script:MariaDbBin "$Name.exe"

    if (-not (Test-Path $executable)) {
        throw "MariaDB portatil nao encontrado em $script:MariaDbRoot."
    }

    return $executable
}

function Test-PortableMariaDb {
    if (-not (Test-Path (Join-Path $script:MariaDbData 'mysql'))) {
        return $false
    }

    $client = Get-MariaDbExecutable 'mariadb'
    $previousPreference = $ErrorActionPreference
    $ErrorActionPreference = 'Continue'
    & $client --skip-ssl --protocol=tcp -h $script:MariaDbHost -P $script:MariaDbPort -u $script:MariaDbUser -e 'SELECT 1' *> $null
    $exitCode = $LASTEXITCODE
    $ErrorActionPreference = $previousPreference

    return $exitCode -eq 0
}

function Start-PortableMariaDb {
    Initialize-LocalDirectories
    Normalize-ProcessPathVariable

    if (Test-PortableMariaDb) {
        Write-Host "MariaDB portatil ja esta ativo na porta $script:MariaDbPort."
        return
    }

    $server = Get-MariaDbExecutable 'mariadbd'
    $process = Start-Process `
        -FilePath $server `
        -ArgumentList @("--defaults-file=`"$script:MariaDbConfig`"", '--console') `
        -WorkingDirectory $script:MariaDbRoot `
        -WindowStyle Hidden `
        -RedirectStandardOutput $script:MariaDbLog `
        -RedirectStandardError $script:MariaDbErrorLog `
        -PassThru

    Set-Content -Path $script:MariaDbPidFile -Value $process.Id -Encoding ASCII
    Start-Sleep -Seconds 3

    if ($process.HasExited -or -not (Test-PortableMariaDb)) {
        throw "Nao foi possivel iniciar o MariaDB portatil. Consulte $script:MariaDbErrorLog."
    }

    Write-Host "MariaDB portatil iniciado em ${script:MariaDbHost}:${script:MariaDbPort}."
}

function Stop-PortableMariaDb {
    if (-not (Test-PortableMariaDb)) {
        return
    }

    $admin = Get-MariaDbExecutable 'mariadb-admin'
    $previousPreference = $ErrorActionPreference
    $ErrorActionPreference = 'Continue'
    & $admin --skip-ssl --protocol=tcp -h $script:MariaDbHost -P $script:MariaDbPort -u $script:MariaDbUser shutdown *> $null
    $ErrorActionPreference = $previousPreference
    Remove-Item $script:MariaDbPidFile -Force -ErrorAction SilentlyContinue
}

function Set-EnvValue([string] $Path, [string] $Name, [string] $Value) {
    $content = Get-Content $Path -Raw
    $line = "$Name=$Value"
    $pattern = "(?m)^$([regex]::Escape($Name))=.*$"

    if ($content -match $pattern) {
        $content = [regex]::Replace($content, $pattern, $line)
    } else {
        $content = $content.TrimEnd() + [Environment]::NewLine + $line + [Environment]::NewLine
    }

    Set-Content -Path $Path -Value $content -Encoding UTF8
}

function Ensure-Database([string] $Name) {
    $client = Get-MariaDbExecutable 'mariadb'
    $previousPreference = $ErrorActionPreference
    $ErrorActionPreference = 'Continue'
    & $client --skip-ssl --protocol=tcp -h $script:MariaDbHost -P $script:MariaDbPort -u $script:MariaDbUser -e "CREATE DATABASE IF NOT EXISTS ``$Name`` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
    $exitCode = $LASTEXITCODE
    $ErrorActionPreference = $previousPreference

    if ($exitCode -ne 0) {
        throw "Nao foi possivel criar o banco $Name."
    }
}

function Test-PortListening([int] $Port) {
    return $null -ne (Get-NetTCPConnection -LocalPort $Port -State Listen -ErrorAction SilentlyContinue | Select-Object -First 1)
}

function Wait-PortListening([int] $Port, [int] $TimeoutSeconds = 15) {
    $deadline = (Get-Date).AddSeconds($TimeoutSeconds)

    do {
        if (Test-PortListening $Port) {
            return $true
        }

        Start-Sleep -Milliseconds 500
    } while ((Get-Date) -lt $deadline)

    return $false
}

function Get-PortProcessId([int] $Port) {
    return (Get-NetTCPConnection -LocalPort $Port -State Listen -ErrorAction SilentlyContinue |
        Select-Object -First 1 -ExpandProperty OwningProcess)
}

function Stop-ProcessTree([int] $ProcessId) {
    Get-CimInstance Win32_Process -Filter "ParentProcessId = $ProcessId" -ErrorAction SilentlyContinue | ForEach-Object {
        Stop-ProcessTree $_.ProcessId
    }

    Stop-Process -Id $ProcessId -Force -ErrorAction SilentlyContinue
}
