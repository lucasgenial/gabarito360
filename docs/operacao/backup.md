# Backup

## Como funciona

| Camada | Onde | Quando | Retenção |
|---|---|---|---|
| 1 | SSD SATA no Proxmox, `/mnt/pve/backup-sata/dump` | 02:30, pelo timer `proxmox-backup-sata` | Hoje e os 7 dias anteriores |
| 2 | Computador do responsável, `D:\Backups-Proxmox\dump` | 06:00, pela tarefa `Copia Backup Proxmox` | 8 datas mais recentes |

A camada 1 faz backup de todos os contêineres, incluindo o CT 111 (Gabarito360) e o CT 106 (MariaDB). Cada arquivo passa por `zstd -t`, recebe SHA-256 e fica imutável. O SSD só fica montado durante o uso.

A camada 2 é iniciada pelo Windows:

1. `D:\Backups-Proxmox\copiar-backups.ps1` chama `/usr/local/sbin/proxmox-backup-copia preparar`.
2. Lista os backups e copia pelo acesso SSH `pve` o que falta.
3. Confere o SHA-256 de cada arquivo, com até 3 tentativas.
4. Só depois remove as datas além das 8 mais recentes.
5. Desmonta o SSD.

Se o computador estiver desligado às 06:00, a tarefa roda quando ele ligar. O servidor recusa a cópia entre 02:00 e 05:00, para não conflitar com o backup.

## Fontes

- `scripts/operacao/proxmox-backup-copia`
- `scripts/operacao/copiar-backups.ps1`

## Verificar

- Última cópia: `D:\Backups-Proxmox\ULTIMA-COPIA-OK.txt`.
- Registros: `D:\Backups-Proxmox\logs`.
- Falhas geram e-mail para o endereço `+gabarito360`, se `RESEND_API_KEY` estiver configurada.
- Situação do SSD: `proxmox-backup-sata status`.

## Teste parcial

Para copiar só alguns arquivos, sem limpeza por retenção:

```powershell
powershell -ExecutionPolicy Bypass -File D:\Backups-Proxmox\copiar-backups.ps1 -Filtro "lxc-305-2026_10_30"
```

## Pendências

- Teste real de restauração do CT 106 e do CT 111.
- Confirmar BitLocker no disco D:. Os arquivos não são criptografados e contêm dados de alunos e segredos dos sistemas.
- Avaliar uma terceira cópia fora do local. As duas camadas ficam no mesmo endereço.
