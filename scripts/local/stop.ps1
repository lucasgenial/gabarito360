. (Join-Path $PSScriptRoot 'common.ps1')

@($script:LaravelServerPidFile, $script:LaravelPidFile) | ForEach-Object {
    if (Test-Path $_) {
        $processId = Get-Content $_ -ErrorAction SilentlyContinue
        $process = Get-Process -Id $processId -ErrorAction SilentlyContinue

        if ($null -ne $process) {
            Stop-ProcessTree $process.Id
        }

        Remove-Item $_ -Force
    }
}

Stop-PortableMariaDb
Write-Host 'Servicos locais encerrados.'
