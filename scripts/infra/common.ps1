$ErrorActionPreference = 'Stop'

$script:RepoRoot = [System.IO.Path]::GetFullPath((Join-Path $PSScriptRoot '..\..'))
$script:DefaultComposeFile = Join-Path $script:RepoRoot 'compose.yaml'
$script:DefaultEnvFile = Join-Path $script:RepoRoot '.env.docker'
$script:LocalRoot = Join-Path $script:RepoRoot '.local'

function Assert-DockerAvailable {
    if ($null -eq (Get-Command docker -ErrorAction SilentlyContinue)) {
        throw 'Docker CLI nao encontrado.'
    }

    & docker info *> $null

    if ($LASTEXITCODE -ne 0) {
        throw 'Docker daemon indisponivel. Inicie o Docker antes de continuar.'
    }
}

function Assert-ComposeInputs([string] $ComposeFile, [string] $EnvFile) {
    if (-not (Test-Path -LiteralPath $ComposeFile -PathType Leaf)) {
        throw "Arquivo Compose nao encontrado: $ComposeFile"
    }

    if (-not (Test-Path -LiteralPath $EnvFile -PathType Leaf)) {
        throw "Arquivo de ambiente nao encontrado: $EnvFile"
    }
}

function Assert-SafeDatabaseName([string] $Database) {
    if ($Database -notmatch '^[A-Za-z0-9_]+$') {
        throw "Nome de banco invalido: $Database"
    }
}

function Assert-PathWithinLocal([string] $Path) {
    $separators = [char[]] @(
        [System.IO.Path]::DirectorySeparatorChar,
        [System.IO.Path]::AltDirectorySeparatorChar
    )
    $resolvedLocal = [System.IO.Path]::GetFullPath($script:LocalRoot).TrimEnd($separators) +
        [System.IO.Path]::DirectorySeparatorChar
    $resolvedPath = [System.IO.Path]::GetFullPath($Path)

    if (-not $resolvedPath.StartsWith($resolvedLocal, [System.StringComparison]::OrdinalIgnoreCase)) {
        throw "Operacao recusada fora de .local: $resolvedPath"
    }
}

function Invoke-Compose(
    [string] $ComposeFile,
    [string] $EnvFile,
    [string[]] $Arguments,
    [switch] $AllowFailure
) {
    & docker compose --env-file $EnvFile -f $ComposeFile @Arguments
    $exitCode = $LASTEXITCODE

    if (-not $AllowFailure -and $exitCode -ne 0) {
        throw "Docker Compose falhou com codigo $exitCode."
    }

}

function Invoke-ComposeOutput(
    [string] $ComposeFile,
    [string] $EnvFile,
    [string[]] $Arguments
) {
    $output = & docker compose --env-file $EnvFile -f $ComposeFile @Arguments

    if ($LASTEXITCODE -ne 0) {
        throw "Docker Compose falhou com codigo $LASTEXITCODE."
    }

    return @($output)
}

function Get-Manifest([string] $BackupDirectory) {
    $manifestPath = Join-Path $BackupDirectory 'manifest.json'

    if (-not (Test-Path -LiteralPath $manifestPath -PathType Leaf)) {
        throw "Manifesto de backup nao encontrado: $manifestPath"
    }

    return Get-Content -LiteralPath $manifestPath -Raw | ConvertFrom-Json
}

function Assert-BackupChecksums([string] $BackupDirectory, [object] $Manifest) {
    foreach ($item in @($Manifest.files.PSObject.Properties)) {
        $path = Join-Path $BackupDirectory $item.Name

        if (-not (Test-Path -LiteralPath $path -PathType Leaf)) {
            throw "Arquivo ausente no backup: $($item.Name)"
        }

        $actual = (Get-FileHash -LiteralPath $path -Algorithm SHA256).Hash.ToLowerInvariant()

        if ($actual -ne [string] $item.Value) {
            throw "Checksum invalido para $($item.Name)."
        }
    }
}
