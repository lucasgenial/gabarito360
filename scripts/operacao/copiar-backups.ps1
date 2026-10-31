<#
  Copia os backups do Proxmox para D:\Backups-Proxmox, com checksum.
  Mantém as 8 datas mais recentes: uma data nova completa elimina a mais antiga.
  Agendado no Windows; documentação em docs/operacao/backup.md.
#>
param([string]$Filtro = '')

$ErrorActionPreference = 'Stop'

$Destino = 'D:\Backups-Proxmox'
$Dump    = Join-Path $Destino 'dump'
$Servidor = 'pve'
$Ajudante = '/usr/local/sbin/proxmox-backup-copia'
$DiasGuardados = 8
$OpcoesSsh = '-o BatchMode=yes -o ConnectTimeout=20 -o ServerAliveInterval=30'

New-Item -ItemType Directory -Force $Dump | Out-Null
$Log = Join-Path $Destino ("logs\copia-{0:yyyy-MM-dd}.log" -f (Get-Date))
New-Item -ItemType Directory -Force (Split-Path $Log) | Out-Null

function Registrar($texto) { "{0:yyyy-MM-dd HH:mm:ss} {1}" -f (Get-Date), $texto | Tee-Object -FilePath $Log -Append }

function Remoto($comando) {
    $saida = cmd /c "ssh $OpcoesSsh $Servidor `"$Ajudante $comando`" 2>&1"
    if ($LASTEXITCODE -ne 0) { throw "servidor recusou '$comando': $($saida -join ' ')" }
    return $saida
}

function DataDoArquivo($nome) { ([regex]::Match($nome, '-(\d{4}_\d{2}_\d{2})-')).Groups[1].Value }

function Avisar($assunto, $texto) {
    $chave = [Environment]::GetEnvironmentVariable('RESEND_API_KEY', 'User')
    if (-not $chave) { return }
    $corpo = @{ from = 'Gabarito360 <nao-responda@gabarito360.com.br>'; to = @('genio.lucas+gabarito360@gmail.com'); subject = $assunto; text = $texto } | ConvertTo-Json
    try {
        Invoke-RestMethod -Method Post -Uri 'https://api.resend.com/emails' -Headers @{ Authorization = "Bearer $chave"; 'User-Agent' = 'gabarito360-backup/1.0' } -ContentType 'application/json; charset=utf-8' -Body $corpo -TimeoutSec 30 | Out-Null
    } catch { Registrar "aviso por e-mail falhou: $($_.Exception.Message)" }
}

$preparado = $false
try {
    Remoto 'preparar' | Out-Null
    $preparado = $true

    $lista = Remoto 'listar' | ForEach-Object {
        $c = "$_" -split "`t"
        if ($c.Count -eq 3) { [pscustomobject]@{ Nome = $c[0]; Tamanho = [int64]$c[1]; Sha = $c[2]; Data = DataDoArquivo $c[0] } }
    }
    if (-not $lista) { throw 'servidor nao listou nenhum backup' }

    if ($Filtro) { $lista = @($lista | Where-Object { $_.Nome -match $Filtro }) }
    $datas = $lista.Data | Sort-Object -Unique -Descending | Select-Object -First $DiasGuardados
    $novos = 0
    foreach ($item in ($lista | Where-Object { $datas -contains $_.Data } | Sort-Object Data, Nome)) {
        $final = Join-Path $Dump $item.Nome
        if ((Test-Path $final) -and (Get-Item $final).Length -eq $item.Tamanho) { continue }
        $parcial = "$final.parcial"
        $certo = $false
        foreach ($tentativa in 1..3) {
            cmd /c "ssh $OpcoesSsh $Servidor `"$Ajudante ler $($item.Nome)`" > `"$parcial`""
            if ($LASTEXITCODE -eq 0 -and (Get-FileHash $parcial -Algorithm SHA256).Hash -ieq $item.Sha) { $certo = $true; break }
            Registrar "checksum ou leitura falhou em $($item.Nome), tentativa $tentativa"
        }
        if (-not $certo) { Remove-Item $parcial -Force -ErrorAction SilentlyContinue; throw "nao foi possivel copiar $($item.Nome)" }
        Move-Item -Force $parcial $final
        $novos++
        Registrar "copiado $($item.Nome)"
    }

    if ($Filtro) { Registrar "teste com filtro concluido: $novos arquivo(s)"; return }

    # Só limpa depois de tudo copiado e conferido
    $manter = Get-ChildItem $Dump -Filter '*.tar.zst' | ForEach-Object { DataDoArquivo $_.Name } | Sort-Object -Unique -Descending | Select-Object -First $DiasGuardados
    Get-ChildItem $Dump -Filter '*.tar.zst' | Where-Object { $manter -notcontains (DataDoArquivo $_.Name) } | ForEach-Object {
        Registrar "removido por retencao $($_.Name)"
        Remove-Item -LiteralPath $_.FullName -Force
    }
    Get-ChildItem (Split-Path $Log) -Filter 'copia-*.log' | Sort-Object LastWriteTime -Descending | Select-Object -Skip 30 | Remove-Item -Force

    $ultimo = ($datas | Select-Object -First 1)
    Registrar "concluido: $novos arquivo(s) novo(s); backup mais recente $ultimo"
    Set-Content (Join-Path $Destino 'ULTIMA-COPIA-OK.txt') ("{0:yyyy-MM-dd HH:mm:ss} backup {1}" -f (Get-Date), $ultimo)
}
catch {
    Registrar "FALHA: $($_.Exception.Message)"
    if ($_.Exception.Message -notmatch 'janela do backup|backup em andamento') {
        Avisar 'Falha na copia de backup para o D:' $_.Exception.Message
    }
    exit 1
}
finally {
    if ($preparado) { try { Remoto 'encerrar' | Out-Null } catch { Registrar "nao desmontou o SSD: $($_.Exception.Message)" } }
}
