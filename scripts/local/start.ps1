. (Join-Path $PSScriptRoot 'common.ps1')

Initialize-LocalDirectories
Start-PortableMariaDb

if (Test-Path $script:LaravelPidFile) {
    $existingPid = Get-Content $script:LaravelPidFile -ErrorAction SilentlyContinue
    $existingProcess = Get-Process -Id $existingPid -ErrorAction SilentlyContinue

    if ($null -ne $existingProcess -and (Test-PortListening $script:AppPort)) {
        Write-Host "Painel ja esta ativo em http://${script:MariaDbHost}:$script:AppPort/admin/login"
        exit 0
    }

    Remove-Item $script:LaravelPidFile -Force
}

if (Test-PortListening $script:AppPort) {
    throw "A porta $script:AppPort ja esta ocupada por outro processo."
}

$php = Get-PhpExecutable
$router = Join-Path $script:BackendRoot 'vendor\laravel\framework\src\Illuminate\Foundation\resources\server.php'
$publicRoot = Join-Path $script:BackendRoot 'public'

if (-not (Test-Path $router)) {
    throw 'Dependencias Laravel ausentes. Execute composer install em backend/.'
}

Normalize-ProcessPathVariable
$process = Start-Process `
    -FilePath $php `
    -ArgumentList @('-S', "${script:MariaDbHost}:$script:AppPort", "`"$router`"") `
    -WorkingDirectory $publicRoot `
    -WindowStyle Hidden `
    -RedirectStandardOutput $script:LaravelLog `
    -RedirectStandardError $script:LaravelErrorLog `
    -PassThru

Set-Content -Path $script:LaravelPidFile -Value $process.Id -Encoding ASCII

if (-not (Wait-PortListening $script:AppPort)) {
    Stop-ProcessTree $process.Id
    throw "Nao foi possivel iniciar o Laravel. Consulte $script:LaravelErrorLog."
}

$serverPid = Get-PortProcessId $script:AppPort
Set-Content -Path $script:LaravelServerPidFile -Value $serverPid -Encoding ASCII

Write-Host "Painel: http://${script:MariaDbHost}:$script:AppPort/admin/login"
Write-Host "API health: http://${script:MariaDbHost}:$script:AppPort/api/v1/health"
