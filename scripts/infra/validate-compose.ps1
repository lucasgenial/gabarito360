param(
    [string] $ComposeFile,
    [string] $EnvFile
)

. (Join-Path $PSScriptRoot 'common.ps1')

$ComposeFile = if ($ComposeFile) { $ComposeFile } else { $script:DefaultComposeFile }
$EnvFile = if ($EnvFile) { $EnvFile } else { $script:DefaultEnvFile }

Assert-ComposeInputs $ComposeFile $EnvFile

$raw = & docker compose --env-file $EnvFile -f $ComposeFile config --format json

if ($LASTEXITCODE -ne 0) {
    throw 'Contrato Compose invalido.'
}

$config = $raw | ConvertFrom-Json
$requiredServices = @('app', 'mariadb', 'migrate', 'nginx', 'queue', 'redis', 'reverb', 'scheduler')
$actualServices = @($config.services.PSObject.Properties.Name)
$missing = @($requiredServices | Where-Object { $_ -notin $actualServices })

if ($missing.Count -gt 0) {
    throw "Servicos obrigatorios ausentes: $($missing -join ', ')"
}

foreach ($service in $config.services.PSObject.Properties) {
    $ports = @($service.Value.ports | Where-Object { $null -ne $_ })

    if ($service.Name -ne 'nginx' -and $ports.Count -gt 0) {
        throw "Servico interno publicou porta: $($service.Name)"
    }
}

if (@($config.services.nginx.ports | Where-Object { $null -ne $_ }).Count -ne 1) {
    throw 'Nginx deve ser a unica entrada publicada.'
}

if (-not $config.networks.internal.internal) {
    throw 'A rede de dados deve ser interna.'
}

foreach ($serviceName in @('mariadb', 'redis')) {
    $networks = @($config.services.$serviceName.networks.PSObject.Properties.Name)

    if ('internal' -notin $networks -or $networks.Count -ne 1) {
        throw "$serviceName deve permanecer exclusivamente na rede interna."
    }
}

Write-Host 'Contrato Compose aprovado: somente Nginx publica porta; MariaDB e Redis estao isolados.'
